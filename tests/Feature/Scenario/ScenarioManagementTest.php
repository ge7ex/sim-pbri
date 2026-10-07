<?php

namespace Tests\Feature\Scenario;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
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
}
