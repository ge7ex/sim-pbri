<?php

namespace Tests\Feature\Scenario;

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
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScenarioManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_management_but_cannot_change_master_data(): void
    {
        [$staff, $course] = $this->context(UserRole::Staff);

        $this->actingAs($staff)->get('/app/scenarios')->assertOk();

        $this->actingAs($staff)
            ->post('/app/scenarios/courses', ['name' => 'รายวิชาใหม่'])
            ->assertForbidden();

        $this->actingAs($staff)
            ->post('/app/scenarios', [
                'course_id' => $course->id,
                'name' => 'Scenario ใหม่',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_lecturer_cannot_open_scenario_management(): void
    {
        [$lecturer] = $this->context(UserRole::Lecturer);

        $this->actingAs($lecturer)
            ->get('/app/scenarios')
            ->assertForbidden();
    }

    public function test_admin_can_create_course_and_scenario_in_own_college(): void
    {
        [$admin] = $this->context(UserRole::Admin);

        $this->actingAs($admin)
            ->post('/app/scenarios/courses', [
                'code' => 'NUR-201',
                'name' => 'การพยาบาลผู้ใหญ่',
            ])
            ->assertRedirect();

        $course = Course::query()->where('code', 'NUR-201')->sole();

        $this->assertSame($admin->college_id, $course->college_id);

        $this->actingAs($admin)
            ->post('/app/scenarios', [
                'course_id' => $course->id,
                'name' => 'ภาวะฉุกเฉินทางการหายใจ',
                'description' => 'Preferred scenario สำหรับรายวิชานี้',
                'is_active' => true,
            ])
            ->assertRedirect();

        $scenario = Scenario::query()
            ->where('name', 'ภาวะฉุกเฉินทางการหายใจ')
            ->sole();

        $this->assertTrue($scenario->course->is($course));
    }

    public function test_admin_can_update_course_without_changing_scenarios_or_booking_history(): void
    {
        [$admin, $course] = $this->context(UserRole::Admin);
        $scenario = Scenario::query()->create([
            'course_id' => $course->id,
            'name' => 'Scenario history',
        ]);
        $booking = $this->booking($admin, $course, $scenario);

        $this->actingAs($admin)
            ->put("/app/scenarios/courses/{$course->id}", [
                'code' => 'NUR-202',
                'name' => 'รายวิชาที่แก้ไข',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'NUR-202',
            'name' => 'รายวิชาที่แก้ไข',
        ]);
        $this->assertDatabaseHas('scenarios', ['id' => $scenario->id, 'course_id' => $course->id]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'course_id' => $course->id, 'scenario_id' => $scenario->id]);
    }

    public function test_admin_can_update_scenario_metadata_without_changing_course_recommendations_or_booking_history(): void
    {
        [$admin, $course] = $this->context(UserRole::Admin);
        $scenario = Scenario::query()->create([
            'course_id' => $course->id,
            'name' => 'Scenario เดิม',
            'description' => 'รายละเอียดเดิม',
            'is_active' => true,
        ]);
        $equipment = SimResource::query()->create([
            'college_id' => $admin->college_id,
            'name' => 'อุปกรณ์แนะนำ',
            'kind' => SimResourceKind::Equipment,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 4,
            'is_exclusive' => false,
        ]);
        $type = SimulatorType::query()->create([
            'college_id' => $admin->college_id,
            'name' => 'ประเภทหุ่นแนะนำ',
        ]);
        $scenario->recommendedResources()->attach($equipment->id, ['quantity' => 2]);
        $scenario->recommendedSimulatorTypes()->attach($type->id);
        $booking = $this->booking($admin, $course, $scenario);

        $this->actingAs($admin)
            ->put("/app/scenarios/{$scenario->id}", [
                'course_id' => $course->id,
                'name' => 'Scenario ใหม่',
                'description' => 'รายละเอียดใหม่',
                'is_active' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('scenarios', [
            'id' => $scenario->id,
            'course_id' => $course->id,
            'name' => 'Scenario ใหม่',
            'description' => 'รายละเอียดใหม่',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('scenario_resource_templates', [
            'scenario_id' => $scenario->id,
            'sim_resource_id' => $equipment->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('scenario_simulator_type', [
            'scenario_id' => $scenario->id,
            'simulator_type_id' => $type->id,
        ]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'scenario_id' => $scenario->id]);
    }

    public function test_staff_and_lecturer_cannot_update_course_or_scenario(): void
    {
        foreach ([UserRole::Staff, UserRole::Lecturer] as $role) {
            [$user, $course] = $this->context($role);
            $scenario = Scenario::query()->create([
                'course_id' => $course->id,
                'name' => 'Scenario read only',
            ]);

            $this->actingAs($user)
                ->put("/app/scenarios/courses/{$course->id}", ['name' => 'ห้ามแก้', 'code' => null])
                ->assertForbidden();
            $this->actingAs($user)
                ->put("/app/scenarios/{$scenario->id}", [
                    'course_id' => $course->id,
                    'name' => 'ห้ามแก้',
                    'description' => null,
                    'is_active' => false,
                ])
                ->assertForbidden();
        }
    }

    public function test_updates_validate_course_and_scenario_fields(): void
    {
        [$admin, $course] = $this->context(UserRole::Admin);
        $scenario = Scenario::query()->create(['course_id' => $course->id, 'name' => 'ชื่อเดิม']);
        Course::query()->create([
            'college_id' => $admin->college_id,
            'code' => 'NUR-201',
            'name' => 'อีกวิชา',
        ]);

        $this->actingAs($admin)
            ->put("/app/scenarios/courses/{$course->id}", ['code' => 'NUR-201', 'name' => ''])
            ->assertSessionHasErrors(['code', 'name']);
        $this->actingAs($admin)
            ->put("/app/scenarios/{$scenario->id}", [
                'course_id' => $course->id,
                'name' => str_repeat('ก', 256),
                'description' => str_repeat('ข', 2001),
                'is_active' => 'invalid',
            ])
            ->assertSessionHasErrors(['name', 'description', 'is_active']);
    }

    public function test_admin_cannot_update_course_or_scenario_from_another_college(): void
    {
        [$admin] = $this->context(UserRole::Admin);
        [$otherAdmin, $otherCourse] = $this->context(UserRole::Admin);

        $scenario = Scenario::query()->create([
            'course_id' => $otherCourse->id,
            'name' => 'Scenario ต่างวิทยาลัย',
        ]);

        $this->actingAs($admin)
            ->put("/app/scenarios/courses/{$otherCourse->id}", [
                'name' => 'เปลี่ยนชื่อ',
                'code' => null,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->put("/app/scenarios/{$scenario->id}", [
                'course_id' => $otherCourse->id,
                'name' => 'เปลี่ยนชื่อ',
                'description' => null,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertNotSame($admin->college_id, $otherAdmin->college_id);
    }

    /**
     * @return array{User, Course}
     */
    private function context(UserRole $role): array
    {
        $college = College::factory()->create();

        $user = User::factory()->create([
            'college_id' => $college->id,
            'role' => $role->value,
        ]);

        $course = Course::query()->create([
            'college_id' => $college->id,
            'name' => 'การพยาบาลพื้นฐาน',
        ]);

        return [$user, $course];
    }

    private function booking(User $user, Course $course, Scenario $scenario): Booking
    {
        return Booking::query()->create([
            'college_id' => $user->college_id,
            'requested_by_user_id' => $user->id,
            'course_id' => $course->id,
            'scenario_id' => $scenario->id,
            'requester_name' => $user->name,
            'starts_at' => '2026-10-12 09:00:00',
            'ends_at' => '2026-10-12 10:00:00',
            'status' => BookingStatus::Pending,
        ]);
    }
}
