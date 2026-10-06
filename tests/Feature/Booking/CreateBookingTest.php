<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_create_pending_booking_for_ready_resource_in_own_college(): void
    {
        [$user, $room] = $this->bookingContext();

        $this->actingAs($user)->post('/app/bookings', [
            'resources' => [
                ['id' => $room->id, 'quantity' => 1],
            ],
            'starts_at' => '2026-10-07 09:00:00',
            'ends_at' => '2026-10-07 11:00:00',
            'participant_count' => 10,
            'requester_phone' => '0812345678',
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::query()->sole();

        $this->assertSame($user->college_id, $booking->college_id);
        $this->assertSame($user->id, $booking->requested_by_user_id);
        $this->assertSame($user->name, $booking->requester_name);
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(1, $booking->resources()->first()->pivot->quantity);
        $this->assertCount(1, $booking->statusTransitions);
    }

    public function test_client_cannot_override_college_actor_or_requester_identity(): void
    {
        [$user, $room] = $this->bookingContext();
        $otherCollege = College::factory()->create();
        $otherUser = User::factory()->create([
            'college_id' => $otherCollege->id,
            'role' => UserRole::Lecturer->value,
        ]);

        $this->actingAs($user)->post('/app/bookings', [
            'college_id' => $otherCollege->id,
            'requested_by_user_id' => $otherUser->id,
            'requester_name' => 'จองแทนโดยไม่ได้รับสิทธิ์',
            'resources' => [
                ['id' => $room->id, 'quantity' => 1],
            ],
            'starts_at' => '2026-10-07 09:00:00',
            'ends_at' => '2026-10-07 11:00:00',
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::query()->sole();

        $this->assertSame($user->college_id, $booking->college_id);
        $this->assertSame($user->id, $booking->requested_by_user_id);
        $this->assertSame($user->name, $booking->requester_name);
    }

    public function test_non_ready_resource_cannot_be_booked(): void
    {
        [$user, $room] = $this->bookingContext(SimResourceStatus::Maintenance);

        $this->actingAs($user)
            ->from('/app/bookings/create')
            ->post('/app/bookings', [
                'resources' => [
                    ['id' => $room->id, 'quantity' => 1],
                ],
                'starts_at' => '2026-10-07 09:00:00',
                'ends_at' => '2026-10-07 11:00:00',
            ])
            ->assertSessionHasErrors('resources');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_resource_from_another_college_cannot_be_booked(): void
    {
        [$user] = $this->bookingContext();
        $otherCollege = College::factory()->create();

        $room = $this->createResource(
            college: $otherCollege,
            name: 'Other College Lab',
        );

        $this->actingAs($user)
            ->from('/app/bookings/create')
            ->post('/app/bookings', [
                'resources' => [
                    ['id' => $room->id, 'quantity' => 1],
                ],
                'starts_at' => '2026-10-07 09:00:00',
                'ends_at' => '2026-10-07 11:00:00',
            ])
            ->assertSessionHasErrors('resources');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_pending_booking_blocks_overlapping_exclusive_resource(): void
    {
        [$user, $room] = $this->bookingContext();

        $this->createViaHttp($user, $room, '09:00', '11:00');

        $this->actingAs($user)
            ->from('/app/bookings/create')
            ->post('/app/bookings', [
                'resources' => [
                    ['id' => $room->id, 'quantity' => 1],
                ],
                'starts_at' => '2026-10-07 10:00:00',
                'ends_at' => '2026-10-07 12:00:00',
            ])
            ->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_nonexclusive_resource_allows_overlap_within_quantity_capacity(): void
    {
        [$user] = $this->bookingContext();

        $equipment = $this->createResource(
            college: $user->college,
            name: 'Patient Monitor',
            kind: SimResourceKind::Equipment,
            quantityTotal: 5,
            isExclusive: false,
        );

        $this->createViaHttp($user, $equipment, '09:00', '11:00', 3);

        $this->actingAs($user)->post('/app/bookings', [
            'resources' => [
                ['id' => $equipment->id, 'quantity' => 2],
            ],
            'starts_at' => '2026-10-07 10:00:00',
            'ends_at' => '2026-10-07 12:00:00',
        ])->assertRedirect();

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_nonexclusive_resource_rejects_overlap_above_quantity_capacity(): void
    {
        [$user] = $this->bookingContext();

        $equipment = $this->createResource(
            college: $user->college,
            name: 'Patient Monitor',
            kind: SimResourceKind::Equipment,
            quantityTotal: 5,
            isExclusive: false,
        );

        $this->createViaHttp($user, $equipment, '09:00', '11:00', 4);

        $this->actingAs($user)
            ->from('/app/bookings/create')
            ->post('/app/bookings', [
                'resources' => [
                    ['id' => $equipment->id, 'quantity' => 2],
                ],
                'starts_at' => '2026-10-07 10:00:00',
                'ends_at' => '2026-10-07 12:00:00',
            ])
            ->assertSessionHasErrors('resources');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_touching_intervals_do_not_overlap(): void
    {
        [$user, $room] = $this->bookingContext();

        $this->createViaHttp($user, $room, '09:00', '10:00');

        $this->createViaHttp($user, $room, '10:00', '11:00')
            ->assertRedirect();

        $this->assertDatabaseCount('bookings', 2);
    }

    /**
     * @return array{User, SimResource}
     */
    private function bookingContext(
        SimResourceStatus $status = SimResourceStatus::Ready,
    ): array {
        $college = College::factory()->create();

        $user = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Lecturer->value,
        ]);

        $room = $this->createResource(
            college: $college,
            name: 'SIM Lab 1',
            status: $status,
        );

        return [$user, $room];
    }

    private function createResource(
        College $college,
        string $name,
        SimResourceKind $kind = SimResourceKind::Room,
        SimResourceStatus $status = SimResourceStatus::Ready,
        int $quantityTotal = 1,
        bool $isExclusive = true,
    ): SimResource {
        return SimResource::query()->create([
            'college_id' => $college->id,
            'name' => $name,
            'kind' => $kind,
            'status' => $status,
            'quantity_total' => $quantityTotal,
            'is_exclusive' => $isExclusive,
        ]);
    }

    private function createViaHttp(
        User $user,
        SimResource $resource,
        string $startTime,
        string $endTime,
        int $quantity = 1,
    ) {
        return $this->actingAs($user)->post('/app/bookings', [
            'resources' => [
                ['id' => $resource->id, 'quantity' => $quantity],
            ],
            'starts_at' => "2026-10-07 {$startTime}:00",
            'ends_at' => "2026-10-07 {$endTime}:00",
        ]);
    }
}
