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

final class BookingStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_approve_pending_booking_and_audit_is_recorded(): void
    {
        [$booking, $staff] = $this->pendingBooking();

        $this->actingAs($staff)
            ->post("/app/bookings/{$booking->id}/approve")
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame(BookingStatus::Approved, $booking->status);
        $this->assertSame($staff->id, $booking->reviewed_by_user_id);
        $this->assertNotNull($booking->reviewed_at);

        $transition = $booking->statusTransitions()->latest('id')->firstOrFail();
        $this->assertSame(BookingStatus::Pending, $transition->from_status);
        $this->assertSame(BookingStatus::Approved, $transition->to_status);
        $this->assertSame($staff->id, $transition->actor_user_id);
    }

    public function test_approval_rechecks_current_resource_capacity(): void
    {
        [$booking, $staff, $requester, $resource] = $this->pendingBooking(
            resourceKind: SimResourceKind::Equipment,
            resourceQuantity: 3,
            quantityTotal: 5,
            isExclusive: false,
        );

        $room = SimResource::query()->create([
            'college_id' => $requester->college_id,
            'name' => 'SIM Lab 2',
            'kind' => SimResourceKind::Room,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 1,
            'is_exclusive' => true,
            'capacity' => 20,
        ]);

        $ownRoom = $room->replicate();
        $ownRoom->name = 'SIM Lab 1';
        $ownRoom->save();
        $booking->resources()->attach($ownRoom->id, ['quantity' => 1]);

        $other = Booking::query()->create([
            'college_id' => $requester->college_id,
            'requested_by_user_id' => $requester->id,
            'requester_name' => $requester->name,
            'participant_count' => 10,
            'requester_phone' => '0812345678',
            'starts_at' => $booking->starts_at,
            'ends_at' => $booking->ends_at,
            'status' => BookingStatus::Pending,
        ]);
        $other->resources()->attach([
            $room->id => ['quantity' => 1],
            $resource->id => ['quantity' => 2],
        ]);

        $resource->update(['quantity_total' => 4]);

        $this->actingAs($staff)
            ->from('/app/review')
            ->post("/app/bookings/{$booking->id}/approve")
            ->assertSessionHasErrors(['resources' => 'ทรัพยากร "Resource 1" มีจำนวนคงเหลือไม่เพียงพอในช่วงเวลาที่เลือก']);

        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
        $this->assertSame(0, $booking->statusTransitions()->count());

        $resource->update(['quantity_total' => 5]);
        $this->actingAs($staff)->post("/app/bookings/{$booking->id}/approve")->assertSessionHasNoErrors();
        $this->assertSame(BookingStatus::Approved, $booking->refresh()->status);
    }

    public function test_approval_is_blocked_when_room_capacity_drops_below_the_requested_participant_count(): void
    {
        [$booking, $staff, , $room] = $this->pendingBooking();
        $room->update(['capacity' => 5]);

        $this->actingAs($staff)->from('/app/review')
            ->post("/app/bookings/{$booking->id}/approve")
            ->assertSessionHasErrors('participant_count');

        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
    }

    public function test_recall_is_blocked_when_room_is_no_longer_ready_or_large_enough(): void
    {
        [$booking, $staff, , $room] = $this->pendingBooking();
        $booking->update(['status' => BookingStatus::Rejected]);
        $room->update(['status' => SimResourceStatus::Maintenance]);

        $this->actingAs($staff)->from('/app/review')->post("/app/bookings/{$booking->id}/recall", [
            'confirmed' => true, 'reason' => 'ตรวจสอบห้อง',
        ])->assertSessionHasErrors('resources');
        $this->assertSame(BookingStatus::Rejected, $booking->refresh()->status);

        $room->update(['status' => SimResourceStatus::Ready, 'capacity' => 5]);
        $this->actingAs($staff)->from('/app/review')->post("/app/bookings/{$booking->id}/recall", [
            'confirmed' => true, 'reason' => 'ตรวจสอบความจุ',
        ])->assertSessionHasErrors('participant_count');
        $this->assertSame(BookingStatus::Rejected, $booking->refresh()->status);
    }

    public function test_rejection_requires_reason_and_is_audited(): void
    {
        [$booking, $staff] = $this->pendingBooking();

        $this->actingAs($staff)
            ->from('/app/review')
            ->post("/app/bookings/{$booking->id}/reject", [])
            ->assertSessionHasErrors('reason');

        $this->actingAs($staff)
            ->post("/app/bookings/{$booking->id}/reject", [
                'reason' => 'ทรัพยากรไม่พร้อมใช้งาน',
            ])
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame(BookingStatus::Rejected, $booking->status);
        $this->assertSame('ทรัพยากรไม่พร้อมใช้งาน', $booking->review_reason);
        $this->assertSame(
            'ทรัพยากรไม่พร้อมใช้งาน',
            $booking->statusTransitions()->latest('id')->value('reason'),
        );
    }

    public function test_recall_requires_confirmation_and_reason(): void
    {
        [$booking, $staff] = $this->pendingBooking();
        $booking->update(['status' => BookingStatus::Approved]);

        $this->actingAs($staff)
            ->from('/app/review')
            ->post("/app/bookings/{$booking->id}/recall", [
                'reason' => 'ตรวจสอบข้อมูลใหม่',
            ])
            ->assertSessionHasErrors('confirmed');

        $this->actingAs($staff)
            ->post("/app/bookings/{$booking->id}/recall", [
                'confirmed' => true,
                'reason' => 'ตรวจสอบข้อมูลใหม่',
            ])
            ->assertRedirect();

        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
        $this->assertSame(
            'ตรวจสอบข้อมูลใหม่',
            $booking->statusTransitions()->latest('id')->value('reason'),
        );
    }

    public function test_recalling_rejected_booking_fails_when_slot_was_taken(): void
    {
        [$booking, $staff, $requester, $resource] = $this->pendingBooking();
        $booking->update(['status' => BookingStatus::Rejected]);

        $other = Booking::query()->create([
            'college_id' => $requester->college_id,
            'requested_by_user_id' => $requester->id,
            'requester_name' => $requester->name,
            'participant_count' => 10,
            'requester_phone' => '0812345678',
            'starts_at' => $booking->starts_at,
            'ends_at' => $booking->ends_at,
            'status' => BookingStatus::Pending,
        ]);
        $other->resources()->attach($resource->id, ['quantity' => 1]);

        $this->actingAs($staff)
            ->from('/app/review')
            ->post("/app/bookings/{$booking->id}/recall", [
                'confirmed' => true,
                'reason' => 'ขอตรวจใหม่',
            ])
            ->assertSessionHasErrors('starts_at');

        $this->assertSame(BookingStatus::Rejected, $booking->refresh()->status);
    }

    public function test_requester_can_cancel_own_pending_booking_but_other_lecturer_cannot(): void
    {
        [$booking, , $requester] = $this->pendingBooking();

        $other = User::factory()->create([
            'college_id' => $requester->college_id,
            'role' => UserRole::Lecturer->value,
        ]);

        $this->actingAs($other)
            ->post("/app/bookings/{$booking->id}/cancel")
            ->assertForbidden();

        $this->actingAs($requester)
            ->post("/app/bookings/{$booking->id}/cancel", [
                'reason' => 'ไม่ใช้งานแล้ว',
            ])
            ->assertRedirect();

        $this->assertSame(BookingStatus::Cancelled, $booking->refresh()->status);
    }

    /**
     * @return array{Booking, User, User, SimResource}
     */
    private function pendingBooking(
        SimResourceKind $resourceKind = SimResourceKind::Room,
        int $resourceQuantity = 1,
        int $quantityTotal = 1,
        bool $isExclusive = true,
    ): array {
        $college = College::factory()->create();

        $requester = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Lecturer->value,
        ]);

        $staff = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Staff->value,
        ]);

        $resource = SimResource::query()->create([
            'college_id' => $college->id,
            'name' => 'Resource 1',
            'kind' => $resourceKind,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => $quantityTotal,
            'is_exclusive' => $isExclusive,
            'capacity' => $resourceKind === SimResourceKind::Room ? 20 : null,
        ]);

        $booking = Booking::query()->create([
            'college_id' => $college->id,
            'requested_by_user_id' => $requester->id,
            'requester_name' => $requester->name,
            'participant_count' => 10,
            'requester_phone' => '0812345678',
            'starts_at' => '2026-10-08 09:00:00',
            'ends_at' => '2026-10-08 11:00:00',
            'status' => BookingStatus::Pending,
        ]);

        $booking->resources()->attach(
            $resource->id,
            ['quantity' => $resourceQuantity],
        );

        return [$booking, $staff, $requester, $resource];
    }
}
