<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ScenarioBookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_accepts_a_different_preferred_course_and_derives_recommendations(): void
    {
        [$user, $room, $college] = $this->bookingContext();
        $preferredCourse = $this->course($college, 'รายวิชาที่สถานการณ์แนะนำ');
        $selectedCourse = $this->course($college, 'รายวิชาที่ผู้ใช้เลือก');
        $scenario = $this->scenario($preferredCourse, 'สถานการณ์แนะนำ');
        $recommended = $this->equipment($college, 'อุปกรณ์ตามสถานการณ์');
        $manual = $this->equipment($college, 'อุปกรณ์เพิ่มเติม');
        $scenario->recommendedResources()->attach($recommended->id, ['quantity' => 2]);

        $this->actingAs($user)->post('/app/bookings', [
            'course_id' => $selectedCourse->id,
            'scenario_id' => $scenario->id,
            'resources' => [
                ['id' => $room->id, 'quantity' => 1, 'is_auto_recommended' => true],
                ['id' => $recommended->id, 'quantity' => 3, 'is_auto_recommended' => false],
                ['id' => $manual->id, 'quantity' => 1, 'is_auto_recommended' => true],
            ],
            'starts_at' => '2026-10-12 09:00:00',
            'ends_at' => '2026-10-12 11:00:00',
            'custom_equipment' => [[
                'name' => 'เครื่องมือเฉพาะกิจที่ไม่มีในแค็ตตาล็อก',
                'quantity' => 2,
                'note' => 'ให้เจ้าหน้าที่พิจารณา',
            ], [
                'name' => 'อุปกรณ์สั่งทำ',
                'quantity' => 1,
                'note' => null,
            ]],
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::query()->sole();
        $this->assertSame($selectedCourse->id, $booking->course_id);
        $this->assertSame($scenario->id, $booking->scenario_id);
        $this->assertSame(0, (int) $booking->resources()->whereKey($room->id)->firstOrFail()->pivot->is_auto_recommended);
        $this->assertSame(1, (int) $booking->resources()->whereKey($recommended->id)->firstOrFail()->pivot->is_auto_recommended);
        $this->assertSame(0, (int) $booking->resources()->whereKey($manual->id)->firstOrFail()->pivot->is_auto_recommended);
        $this->assertSame(3, $booking->resources()->whereKey($recommended->id)->firstOrFail()->pivot->quantity);
        $this->assertDatabaseHas('booking_custom_equipment_requests', [
            'booking_id' => $booking->id,
            'name' => 'เครื่องมือเฉพาะกิจที่ไม่มีในแค็ตตาล็อก',
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('booking_custom_equipment_requests', [
            'booking_id' => $booking->id,
            'name' => 'อุปกรณ์สั่งทำ',
            'quantity' => 1,
        ]);
        $this->assertDatabaseCount('booking_custom_equipment_requests', 2);
    }

    public function test_user_can_omit_recommended_items_and_cannot_spoof_recommendation_flags(): void
    {
        [$user, $room, $college] = $this->bookingContext();
        $course = $this->course($college, 'รายวิชา');
        $scenario = $this->scenario($course, 'สถานการณ์');
        $recommended = $this->equipment($college, 'อุปกรณ์แนะนำ');
        $scenario->recommendedResources()->attach($recommended->id, ['quantity' => 1]);

        $this->actingAs($user)->post('/app/bookings', [
            'scenario_id' => $scenario->id,
            'resources' => [
                ['id' => $room->id, 'quantity' => 1, 'is_auto_recommended' => true],
            ],
            'starts_at' => '2026-10-12 09:00:00',
            'ends_at' => '2026-10-12 11:00:00',
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::query()->sole();
        $this->assertDatabaseMissing('booking_resource', ['booking_id' => $booking->id, 'sim_resource_id' => $recommended->id]);
        $this->assertDatabaseHas('booking_resource', [
            'booking_id' => $booking->id,
            'sim_resource_id' => $room->id,
            'is_auto_recommended' => false,
        ]);
    }

    public function test_course_and_scenario_must_belong_to_the_actor_college_and_scenario_must_be_active(): void
    {
        [$user, $room, $college] = $this->bookingContext();
        $ownCourse = $this->course($college, 'รายวิชาของฉัน');
        $otherCollege = College::factory()->create();
        $otherCourse = $this->course($otherCollege, 'รายวิชาต่างวิทยาลัย');
        $otherScenario = $this->scenario($otherCourse, 'สถานการณ์ต่างวิทยาลัย');
        $inactive = $this->scenario($ownCourse, 'สถานการณ์ปิดใช้งาน', false);
        $payload = [
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'starts_at' => '2026-10-12 09:00:00',
            'ends_at' => '2026-10-12 11:00:00',
        ];

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            ...$payload,
            'course_id' => $otherCourse->id,
        ])->assertSessionHasErrors('course_id');

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            ...$payload,
            'scenario_id' => $otherScenario->id,
        ])->assertSessionHasErrors('scenario_id');

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            ...$payload,
            'scenario_id' => $inactive->id,
        ])->assertSessionHasErrors('scenario_id');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_invalid_custom_equipment_quantity_and_more_than_fifty_rows_are_rejected(): void
    {
        [$user, $room] = $this->bookingContext();
        $payload = [
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'starts_at' => '2026-10-12 09:00:00',
            'ends_at' => '2026-10-12 11:00:00',
        ];

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            ...$payload,
            'custom_equipment' => [['name' => 'จำนวนไม่ถูกต้อง', 'quantity' => 0]],
        ])->assertSessionHasErrors('custom_equipment.0.quantity');

        $tooManyRows = array_fill(0, 51, [
            'name' => 'คำขอจำนวนมาก',
            'quantity' => 1,
        ]);

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            ...$payload,
            'custom_equipment' => $tooManyRows,
        ])->assertSessionHasErrors('custom_equipment');

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            ...$payload,
            'custom_equipment' => [[]],
        ])->assertSessionHasErrors([
            'custom_equipment.0.name',
            'custom_equipment.0.quantity',
        ]);

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_custom_equipment_requests', 0);
    }

    public function test_create_form_lists_only_own_college_courses_and_active_scenarios(): void
    {
        [$user, , $college] = $this->bookingContext();
        $ownCourse = $this->course($college, 'รายวิชาของหน่วยงาน');
        $activeScenario = $this->scenario($ownCourse, 'สถานการณ์ใช้งาน');
        $recommended = $this->equipment($college, 'อุปกรณ์ที่แนะนำ');
        $activeScenario->recommendedResources()->attach($recommended->id, ['quantity' => 1]);
        $this->scenario($ownCourse, 'สถานการณ์ไม่ใช้งาน', false);

        $otherCourse = $this->course(College::factory()->create(), 'รายวิชาอื่น');
        $this->scenario($otherCourse, 'สถานการณ์อื่น');

        $this->actingAs($user)->get('/app/bookings/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Modules/Booking/Pages/Create')
                ->has('courses', 1)
                ->has('scenarios', 1)
                ->has('scenarios.0.recommended_resources', 1));
    }

    public function test_same_college_staff_review_sees_course_scenario_and_custom_equipment(): void
    {
        [$user, $room, $college] = $this->bookingContext();
        $course = $this->course($college, 'รายวิชาทดสอบ');
        $scenario = $this->scenario($course, 'สถานการณ์ทดสอบ');

        $this->actingAs($user)->post('/app/bookings', [
            'course_id' => $course->id,
            'scenario_id' => $scenario->id,
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'starts_at' => '2026-10-12 09:00:00',
            'ends_at' => '2026-10-12 11:00:00',
            'custom_equipment' => [[
                'name' => 'อุปกรณ์นอกแค็ตตาล็อก',
                'quantity' => 1,
                'note' => 'ตรวจสอบก่อนอนุมัติ',
            ]],
        ])->assertRedirect();

        $staff = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Staff->value,
        ]);

        $this->actingAs($staff)->get('/app/review')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Modules/Booking/Pages/Review')
                ->where('bookings.data.0.course.name', 'รายวิชาทดสอบ')
                ->where('bookings.data.0.scenario.name', 'สถานการณ์ทดสอบ')
                ->where('bookings.data.0.custom_equipment_requests.0.name', 'อุปกรณ์นอกแค็ตตาล็อก'));
    }

    public function test_historical_booking_with_inactive_scenario_remains_readable(): void
    {
        [$user, $room, $college] = $this->bookingContext();
        $course = $this->course($college, 'รายวิชาย้อนหลัง');
        $scenario = $this->scenario($course, 'สถานการณ์ที่ปิดภายหลัง');
        $this->actingAs($user)->post('/app/bookings', [
            'course_id' => $course->id,
            'scenario_id' => $scenario->id,
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'starts_at' => '2026-10-12 09:00:00',
            'ends_at' => '2026-10-12 11:00:00',
        ])->assertRedirect();

        $scenario->update(['is_active' => false]);
        $booking = Booking::query()->sole();

        $this->actingAs($user)->get("/app/bookings/{$booking->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Modules/Booking/Pages/Show')
                ->where('booking.course.name', 'รายวิชาย้อนหลัง')
                ->where('booking.scenario.name', 'สถานการณ์ที่ปิดภายหลัง'));
    }

    public function test_booking_detail_does_not_expose_cross_college_related_records(): void
    {
        [$user, , $college] = $this->bookingContext();
        $otherCollege = College::factory()->create();
        $otherCourse = $this->course($otherCollege, 'รายวิชาต่างวิทยาลัย');
        $otherScenario = $this->scenario($otherCourse, 'สถานการณ์ต่างวิทยาลัย');
        $otherResource = $this->equipment($otherCollege, 'อุปกรณ์ต่างวิทยาลัย');
        $booking = Booking::query()->create([
            'college_id' => $college->id,
            'requested_by_user_id' => $user->id,
            'course_id' => $otherCourse->id,
            'scenario_id' => $otherScenario->id,
            'requester_name' => $user->name,
            'starts_at' => '2026-10-12 09:00:00',
            'ends_at' => '2026-10-12 11:00:00',
            'status' => BookingStatus::Pending,
        ]);
        $booking->resources()->attach($otherResource->id, ['quantity' => 1]);

        $this->actingAs($user)->get("/app/bookings/{$booking->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Modules/Booking/Pages/Show')
                ->where('booking.course', null)
                ->where('booking.scenario', null)
                ->has('booking.resources', 0));

        $staff = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Staff->value,
        ]);

        $this->actingAs($staff)->get('/app/review')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Modules/Booking/Pages/Review')
                ->where('bookings.data.0.course', null)
                ->where('bookings.data.0.scenario', null)
                ->has('bookings.data.0.resources', 0));
    }

    /**
     * @return array{User, SimResource, College}
     */
    private function bookingContext(): array
    {
        $college = College::factory()->create();
        $user = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Lecturer->value,
        ]);
        $room = SimResource::query()->create([
            'college_id' => $college->id,
            'name' => 'ห้องจำลอง',
            'kind' => SimResourceKind::Room,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 1,
            'is_exclusive' => true,
        ]);

        return [$user, $room, $college];
    }

    private function course(College $college, string $name): Course
    {
        return Course::query()->create(['college_id' => $college->id, 'name' => $name]);
    }

    private function scenario(Course $course, string $name, bool $active = true): Scenario
    {
        return Scenario::query()->create([
            'course_id' => $course->id,
            'name' => $name,
            'is_active' => $active,
        ]);
    }

    private function equipment(College $college, string $name): SimResource
    {
        return SimResource::query()->create([
            'college_id' => $college->id,
            'name' => $name,
            'kind' => SimResourceKind::Equipment,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 5,
            'is_exclusive' => false,
        ]);
    }
}
