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
            'participant_count' => 10,
            'requester_phone' => '0812345678',
            'starts_at' => '2026-10-07 09:00:00',
            'ends_at' => '2026-10-07 11:00:00',
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::query()->sole();

        $this->assertSame($user->college_id, $booking->college_id);
        $this->assertSame($user->id, $booking->requested_by_user_id);
        $this->assertSame($user->name, $booking->requester_name);
    }

    public function test_booking_requires_exactly_one_room(): void
    {
        [$user] = $this->bookingContext();

        $equipment = $this->createResource(
            college: $user->college,
            name: 'Patient Monitor',
            kind: SimResourceKind::Equipment,
            quantityTotal: 5,
            isExclusive: false,
        );

        $this->actingAs($user)
            ->from('/app/bookings/create')
            ->post('/app/bookings', [
                'resources' => [
                    ['id' => $equipment->id, 'quantity' => 1],
                ],
                'participant_count' => 10,
                'requester_phone' => '0812345678',
                'starts_at' => '2026-10-07 09:00:00',
                'ends_at' => '2026-10-07 11:00:00',
            ])
            ->assertSessionHasErrors('resources');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_requires_participant_count_and_phone_and_enforces_room_capacity(): void
    {
        [$user, $room] = $this->bookingContext();
        $base = [
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'starts_at' => '2026-10-07 09:00:00',
            'ends_at' => '2026-10-07 11:00:00',
        ];

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            ...$base, 'participant_count' => 10,
        ])->assertSessionHasErrors('requester_phone');

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            ...$base, 'requester_phone' => '0812345678',
        ])->assertSessionHasErrors('participant_count');

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            ...$base, 'participant_count' => 10, 'requester_phone' => '-------',
        ])->assertSessionHasErrors('requester_phone');

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            ...$base, 'participant_count' => 41, 'requester_phone' => '0812345678',
        ])->assertSessionHasErrors('participant_count');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_legacy_room_without_capacity_is_readable_but_cannot_be_booked(): void
    {
        [$user, $room] = $this->bookingContext();
        $room->update(['capacity' => null]);

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'starts_at' => '2026-10-07 09:00:00',
            'ends_at' => '2026-10-07 11:00:00',
            'participant_count' => 10,
            'requester_phone' => '0812345678',
        ])->assertSessionHasErrors('resources');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_formatted_thai_phone_is_accepted_and_preserved(): void
    {
        [$user, $room] = $this->bookingContext();
        $phone = '+66 (81) 234-5678';

        $this->actingAs($user)->post('/app/bookings', [
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'starts_at' => '2026-10-07 09:00:00', 'ends_at' => '2026-10-07 11:00:00',
            'participant_count' => 12, 'requester_phone' => $phone,
        ])->assertRedirect(route('bookings.index'));

        $this->assertSame($phone, Booking::query()->sole()->requester_phone);
    }

    public function test_room_availability_only_returns_eligible_own_college_ids_and_boolean_status(): void
    {
        [$user, $room] = $this->bookingContext();
        $other = $this->createResource(College::factory()->create(), 'ห้องต่างหน่วยงาน');
        $this->createViaHttp($user, $room, '09:00', '11:00');

        $this->actingAs($user)->get('/app/resources/availability?starts_at=2026-10-07T10:00:00Z&ends_at=2026-10-07T12:00:00Z')
            ->assertOk()
            ->assertExactJson(['rooms' => [['id' => $room->id, 'available' => false]]])
            ->assertJsonMissing(['id' => $other->id]);
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
                'participant_count' => 10,
                'requester_phone' => '0812345678',
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
                'participant_count' => 10,
                'requester_phone' => '0812345678',
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
                'participant_count' => 10,
                'requester_phone' => '0812345678',
                'starts_at' => '2026-10-07 10:00:00',
                'ends_at' => '2026-10-07 12:00:00',
            ])
            ->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_nonexclusive_resource_allows_overlap_within_quantity_capacity(): void
    {
        [$user, $room] = $this->bookingContext();

        $equipment = $this->createResource(
            college: $user->college,
            name: 'Patient Monitor',
            kind: SimResourceKind::Equipment,
            quantityTotal: 5,
            isExclusive: false,
        );

        $this->createViaHttp($user, $room, '09:00', '11:00', 1, $equipment, 3);

        $secondRoom = $this->createResource(
            college: $user->college,
            name: 'SIM Lab 2',
        );

        $this->createViaHttp($user, $secondRoom, '10:00', '12:00', 1, $equipment, 2)
            ->assertRedirect();

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_nonexclusive_resource_rejects_overlap_above_quantity_capacity(): void
    {
        [$user, $room] = $this->bookingContext();

        $equipment = $this->createResource(
            college: $user->college,
            name: 'Patient Monitor',
            kind: SimResourceKind::Equipment,
            quantityTotal: 5,
            isExclusive: false,
        );

        $this->createViaHttp($user, $room, '09:00', '11:00', 1, $equipment, 4);

        $secondRoom = $this->createResource(
            college: $user->college,
            name: 'SIM Lab 2',
        );

        $this->actingAs($user)
            ->from('/app/bookings/create')
            ->post('/app/bookings', [
                'resources' => [
                    ['id' => $secondRoom->id, 'quantity' => 1],
                    ['id' => $equipment->id, 'quantity' => 2],
                ],
                'participant_count' => 10,
                'requester_phone' => '0812345678',
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
            'capacity' => $kind === SimResourceKind::Room ? 40 : null,
        ]);
    }

    private function createViaHttp(
        User $user,
        SimResource $room,
        string $startTime,
        string $endTime,
        int $roomQuantity = 1,
        ?SimResource $equipment = null,
        int $equipmentQuantity = 1,
    ) {
        $resources = [
            ['id' => $room->id, 'quantity' => $roomQuantity],
        ];

        if ($equipment !== null) {
            $resources[] = [
                'id' => $equipment->id,
                'quantity' => $equipmentQuantity,
            ];
        }

        return $this->actingAs($user)->post('/app/bookings', [
            'resources' => $resources,
            'participant_count' => 10,
            'requester_phone' => '0812345678',
            'starts_at' => "2026-10-07 {$startTime}:00",
            'ends_at' => "2026-10-07 {$endTime}:00",
        ]);
    }
}
