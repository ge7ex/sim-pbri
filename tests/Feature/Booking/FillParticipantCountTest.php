<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Actions\FillParticipantCountAction;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class FillParticipantCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_fill_missing_count_and_approve_with_atomic_audit(): void
    {
        [$booking, $staff] = $this->context();
        $this->actingAs($staff)->post("/app/bookings/{$booking->id}/participant-count", [
            'participant_count' => 12, 'reason' => 'Confirmed with requester',
            'college_id' => 999, 'actor_user_id' => 999, 'status' => 'approved',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(12, $booking->refresh()->participant_count);
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertDatabaseHas('booking_participant_amendments', [
            'booking_id' => $booking->id, 'actor_user_id' => $staff->id,
            'previous_count' => null, 'participant_count' => 12, 'reason' => 'Confirmed with requester',
        ]);
        $this->assertSame(0, $booking->statusTransitions()->count());
        $this->actingAs($staff)->get("/app/bookings/{$booking->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('canFillParticipantCount', false)->has('booking.participant_amendments', 1));
        $this->actingAs($staff)->post("/app/bookings/{$booking->id}/approve")->assertSessionHasNoErrors();
        $this->assertSame(BookingStatus::Approved, $booking->refresh()->status);
    }

    public function test_admin_can_fill_rejected_count_then_recall(): void
    {
        [$booking, $staff] = $this->context();
        $staff->update(['role' => UserRole::Admin->value]);
        $booking->update(['status' => BookingStatus::Rejected]);
        $this->actingAs($staff)->post("/app/bookings/{$booking->id}/participant-count", ['participant_count' => 20, 'reason' => 'Confirmed'])->assertSessionHasNoErrors();
        $this->actingAs($staff)->post("/app/bookings/{$booking->id}/recall", ['confirmed' => true, 'reason' => 'Recheck'])->assertSessionHasNoErrors();
        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
    }

    public function test_lecturer_and_foreign_staff_cannot_amend_or_read_foreign_booking(): void
    {
        [$booking, $staff, $lecturer] = $this->context();
        $foreign = User::factory()->create(['college_id' => College::factory()->create()->id, 'role' => UserRole::Staff->value]);
        foreach ([$lecturer, $foreign] as $actor) {
            $this->actingAs($actor)->post("/app/bookings/{$booking->id}/participant-count", ['participant_count' => 10, 'reason' => 'Confirmed'])->assertForbidden();
        }
        $this->actingAs($foreign)->get("/app/bookings/{$booking->id}")->assertForbidden();
        $this->assertNull($booking->refresh()->participant_count);
        $this->assertDatabaseCount('booking_participant_amendments', 0);
    }

    public function test_invalid_counts_and_reasons_do_not_mutate_or_audit(): void
    {
        [$booking, $staff] = $this->context();
        foreach ([null, 0, -1, 21, 1.5, 'ten'] as $count) {
            $this->actingAs($staff)->from("/app/bookings/{$booking->id}")->post("/app/bookings/{$booking->id}/participant-count", ['participant_count' => $count, 'reason' => 'Confirmed'])->assertSessionHasErrors('participant_count');
        }
        foreach (['', '   ', str_repeat('a', 2001)] as $reason) {
            $this->actingAs($staff)->post("/app/bookings/{$booking->id}/participant-count", ['participant_count' => 10, 'reason' => $reason])->assertSessionHasErrors('reason');
        }
        $this->assertNull($booking->refresh()->participant_count);
        $this->assertDatabaseCount('booking_participant_amendments', 0);
    }

    public function test_existing_count_and_cancelled_booking_cannot_be_overwritten(): void
    {
        [$booking, $staff] = $this->context();
        $booking->update(['participant_count' => 10]);
        $this->actingAs($staff)->post("/app/bookings/{$booking->id}/participant-count", ['participant_count' => 11, 'reason' => 'Change'])->assertForbidden();
        $this->assertSame(10, $booking->refresh()->participant_count);
        $booking->update(['participant_count' => null, 'status' => BookingStatus::Cancelled]);
        $this->actingAs($staff)->post("/app/bookings/{$booking->id}/participant-count", ['participant_count' => 11, 'reason' => 'Change'])->assertForbidden();
        $this->assertDatabaseCount('booking_participant_amendments', 0);
    }

    public function test_legacy_booking_is_readable_and_room_eligibility_is_rechecked(): void
    {
        [$booking, $staff, , $room] = $this->context();
        $room->update(['capacity' => null]);
        $this->actingAs($staff)->get("/app/bookings/{$booking->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('booking.participant_count', null)->where('canFillParticipantCount', true));
        $this->actingAs($staff)->post("/app/bookings/{$booking->id}/participant-count", ['participant_count' => 10, 'reason' => 'Confirmed'])->assertSessionHasErrors('resources');
        $room->update(['capacity' => 20, 'status' => SimResourceStatus::Maintenance]);
        $this->actingAs($staff)->post("/app/bookings/{$booking->id}/participant-count", ['participant_count' => 10, 'reason' => 'Confirmed'])->assertSessionHasErrors('resources');
        $this->assertNull($booking->refresh()->participant_count);
        $this->assertDatabaseCount('booking_participant_amendments', 0);
    }

    public function test_action_rechecks_current_record_when_caller_has_stale_null_count(): void
    {
        [$booking, $staff] = $this->context();
        Booking::whereKey($booking->id)->update(['participant_count' => 10]);
        try {
            app(FillParticipantCountAction::class)->execute($booking, $staff, 12, 'Confirmed');
            $this->fail('Stale caller overwrote an existing count');
        } catch (AuthorizationException $exception) {
            $this->assertSame(10, $booking->refresh()->participant_count);
            $this->assertDatabaseCount('booking_participant_amendments', 0);
        }
    }

    public function test_filling_approved_legacy_booking_preserves_approval_and_occupancy(): void
    {
        [$booking, $staff] = $this->context();
        $booking->update(['status' => BookingStatus::Approved, 'reviewed_by_user_id' => $staff->id, 'reviewed_at' => now()]);
        $this->actingAs($staff)->post("/app/bookings/{$booking->id}/participant-count", ['participant_count' => 10, 'reason' => 'Confirmed'])->assertSessionHasNoErrors();
        $this->assertSame(BookingStatus::Approved, $booking->refresh()->status);
        $this->assertSame($staff->id, $booking->reviewed_by_user_id);
        $this->assertSame(1, $booking->resources()->count());
        $this->assertDatabaseCount('booking_participant_amendments', 1);
    }

    private function context(): array
    {
        $college = College::factory()->create();
        $staff = User::factory()->create(['college_id' => $college->id, 'role' => UserRole::Staff->value]);
        $lecturer = User::factory()->create(['college_id' => $college->id, 'role' => UserRole::Lecturer->value]);
        $room = SimResource::create(['college_id' => $college->id, 'name' => 'Room', 'kind' => SimResourceKind::Room, 'status' => SimResourceStatus::Ready, 'quantity_total' => 1, 'is_exclusive' => true, 'capacity' => 20]);
        $booking = Booking::create(['college_id' => $college->id, 'requested_by_user_id' => $lecturer->id, 'requester_name' => $lecturer->name, 'starts_at' => '2026-10-08 09:00:00', 'ends_at' => '2026-10-08 11:00:00', 'status' => BookingStatus::Pending]);
        $booking->resources()->attach($room->id, ['quantity' => 1]);

        return [$booking, $staff, $lecturer, $room];
    }
}
