<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BookingFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_persists_organizational_and_actor_boundaries(): void
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
            'starts_at' => '2026-10-07 09:00:00',
            'ends_at' => '2026-10-07 11:00:00',
            'participant_count' => 20,
            'status' => BookingStatus::Pending,
        ]);

        $this->assertTrue($booking->college->is($college));
        $this->assertTrue($booking->requestedBy->is($actor));
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(20, $booking->participant_count);
    }

    public function test_only_pending_and_approved_statuses_block_the_calendar(): void
    {
        $this->assertTrue(BookingStatus::Pending->blocksCalendar());
        $this->assertTrue(BookingStatus::Approved->blocksCalendar());

        $this->assertFalse(BookingStatus::Rejected->blocksCalendar());
        $this->assertFalse(BookingStatus::Cancelled->blocksCalendar());
    }

    public function test_status_transition_records_actor_reason_and_previous_state(): void
    {
        $college = College::factory()->create();

        $requester = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Lecturer->value,
        ]);

        $reviewer = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Staff->value,
        ]);

        $booking = Booking::query()->create([
            'college_id' => $college->id,
            'requested_by_user_id' => $requester->id,
            'requester_name' => $requester->name,
            'starts_at' => '2026-10-07 09:00:00',
            'ends_at' => '2026-10-07 11:00:00',
            'status' => BookingStatus::Approved,
        ]);

        $transition = $booking->statusTransitions()->create([
            'from_status' => BookingStatus::Approved,
            'to_status' => BookingStatus::Pending,
            'actor_user_id' => $reviewer->id,
            'reason' => 'เรียกกลับเพื่อตรวจสอบข้อมูลอีกครั้ง',
        ]);

        $this->assertSame(BookingStatus::Approved, $transition->from_status);
        $this->assertSame(BookingStatus::Pending, $transition->to_status);
        $this->assertTrue($transition->actor->is($reviewer));
        $this->assertSame(
            'เรียกกลับเพื่อตรวจสอบข้อมูลอีกครั้ง',
            $transition->reason,
        );
    }
}
