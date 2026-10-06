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

final class SimResourceFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_ready_resources_are_bookable(): void
    {
        $this->assertTrue(SimResourceStatus::Ready->isBookable());
        $this->assertFalse(SimResourceStatus::Pending->isBookable());
        $this->assertFalse(SimResourceStatus::Maintenance->isBookable());
    }

    public function test_booking_can_reserve_room_and_equipment_with_quantities(): void
    {
        $college = College::factory()->create();
        $actor = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Lecturer->value,
        ]);

        $booking = Booking::query()->create([
            'college_id' => $college->id,
            'requested_by_user_id' => $actor->id,
            'requester_name' => $actor->name,
            'starts_at' => '2026-10-08 09:00:00',
            'ends_at' => '2026-10-08 11:00:00',
            'status' => BookingStatus::Pending,
        ]);

        $room = SimResource::query()->create([
            'college_id' => $college->id,
            'name' => 'SIM Lab',
            'kind' => SimResourceKind::Room,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 1,
            'is_exclusive' => true,
        ]);

        $equipment = SimResource::query()->create([
            'college_id' => $college->id,
            'name' => 'Monitor',
            'kind' => SimResourceKind::Equipment,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 10,
            'is_exclusive' => false,
        ]);

        $booking->resources()->attach([
            $room->id => ['quantity' => 1],
            $equipment->id => ['quantity' => 2],
        ]);

        $this->assertCount(2, $booking->resources);
        $this->assertSame(1, $booking->resources->firstWhere('id', $room->id)?->pivot->quantity);
        $this->assertSame(2, $booking->resources->firstWhere('id', $equipment->id)?->pivot->quantity);
    }
}
