<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class BookingPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_redacts_cross_college_booking_details(): void
    {
        $viewerCollege = College::factory()->create();
        $otherCollege = College::factory()->create();

        $viewer = User::factory()->create([
            'college_id' => $viewerCollege->id,
            'role' => UserRole::Lecturer->value,
        ]);

        $otherUser = User::factory()->create([
            'college_id' => $otherCollege->id,
            'role' => UserRole::Lecturer->value,
        ]);

        Booking::query()->create([
            'college_id' => $otherCollege->id,
            'requested_by_user_id' => $otherUser->id,
            'requester_name' => 'ข้อมูลที่ห้ามเปิดเผย',
            'requester_phone' => '0811111111',
            'starts_at' => '2026-10-10 09:00:00',
            'ends_at' => '2026-10-10 11:00:00',
            'note' => 'รายละเอียดลับของคำขอ',
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($viewer)
            ->get('/app/calendar')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Booking/Calendar')
                ->has('events', 1)
                ->where('events.0', [
                    'starts_at' => '2026-10-10T09:00:00+00:00',
                    'ends_at' => '2026-10-10T11:00:00+00:00',
                    'title' => 'ถูกจองแล้ว',
                ]));
    }

    public function test_booking_detail_from_other_college_is_forbidden(): void
    {
        $viewerCollege = College::factory()->create();
        $otherCollege = College::factory()->create();

        $viewer = User::factory()->create([
            'college_id' => $viewerCollege->id,
            'role' => UserRole::Lecturer->value,
        ]);

        $owner = User::factory()->create([
            'college_id' => $otherCollege->id,
            'role' => UserRole::Lecturer->value,
        ]);

        $booking = Booking::query()->create([
            'college_id' => $otherCollege->id,
            'requested_by_user_id' => $owner->id,
            'requester_name' => $owner->name,
            'starts_at' => '2026-10-10 09:00:00',
            'ends_at' => '2026-10-10 11:00:00',
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($viewer)
            ->get("/app/bookings/{$booking->id}")
            ->assertForbidden();
    }

    public function test_booking_history_is_scoped_to_viewers_college(): void
    {
        $viewerCollege = College::factory()->create();
        $otherCollege = College::factory()->create();

        $viewer = User::factory()->create([
            'college_id' => $viewerCollege->id,
            'role' => UserRole::Lecturer->value,
        ]);

        $ownBooking = Booking::query()->create([
            'college_id' => $viewerCollege->id,
            'requested_by_user_id' => $viewer->id,
            'requester_name' => $viewer->name,
            'starts_at' => '2026-10-10 09:00:00',
            'ends_at' => '2026-10-10 11:00:00',
            'status' => BookingStatus::Pending,
        ]);

        $otherUser = User::factory()->create([
            'college_id' => $otherCollege->id,
            'role' => UserRole::Lecturer->value,
        ]);

        Booking::query()->create([
            'college_id' => $otherCollege->id,
            'requested_by_user_id' => $otherUser->id,
            'requester_name' => $otherUser->name,
            'starts_at' => '2026-10-10 12:00:00',
            'ends_at' => '2026-10-10 13:00:00',
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($viewer)
            ->get('/app/bookings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Booking/Index')
                ->has('bookings.data', 1)
                ->where('bookings.data.0.id', $ownBooking->id));
    }
}
