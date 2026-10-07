<?php

namespace Tests\Feature\Scenario;

use App\Models\College;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingCustomEquipmentRequest;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScenarioPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_course_relation_is_preference_not_booking_constraint(): void
    {
        $college = College::factory()->create();

        $preferredCourse = Course::query()->create([
            'college_id' => $college->id,
            'name' => 'รายวิชาที่แนะนำ',
        ]);

        $selectedCourse = Course::query()->create([
            'college_id' => $college->id,
            'name' => 'รายวิชาที่ผู้ใช้เลือกจริง',
        ]);

        $scenario = Scenario::query()->create([
            'course_id' => $preferredCourse->id,
            'name' => 'สถานการณ์จำลองตัวอย่าง',
        ]);

        $user = \App\Models\User::factory()->create([
            'college_id' => $college->id,
            'role' => \App\Core\Enums\UserRole::Lecturer->value,
        ]);

        $booking = Booking::query()->create([
            'college_id' => $college->id,
            'requested_by_user_id' => $user->id,
            'course_id' => $selectedCourse->id,
            'scenario_id' => $scenario->id,
            'requester_name' => $user->name,
            'starts_at' => '2026-10-12 09:00:00',
            'ends_at' => '2026-10-12 10:00:00',
            'status' => BookingStatus::Pending,
        ]);

        $this->assertTrue($booking->course->is($selectedCourse));
        $this->assertTrue($booking->scenario->is($scenario));
        $this->assertFalse($booking->course->is($scenario->course));
    }

    public function test_booking_can_store_custom_equipment_request_outside_catalog(): void
    {
        $college = College::factory()->create();

        $user = \App\Models\User::factory()->create([
            'college_id' => $college->id,
            'role' => \App\Core\Enums\UserRole::Lecturer->value,
        ]);

        $booking = Booking::query()->create([
            'college_id' => $college->id,
            'requested_by_user_id' => $user->id,
            'requester_name' => $user->name,
            'starts_at' => '2026-10-12 09:00:00',
            'ends_at' => '2026-10-12 10:00:00',
            'status' => BookingStatus::Pending,
        ]);

        $request = BookingCustomEquipmentRequest::query()->create([
            'booking_id' => $booking->id,
            'name' => 'อุปกรณ์เฉพาะกิจ',
            'quantity' => 2,
            'note' => 'ขอให้เจ้าหน้าที่ตรวจสอบความพร้อม',
        ]);

        $this->assertTrue($request->booking->is($booking));
        $this->assertSame(2, $request->quantity);
    }
}
