<?php

namespace Tests\Feature\Scenario;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScenarioEquipmentTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_replace_scenario_equipment_template(): void
    {
        [$admin, $scenario, $equipmentA, $equipmentB] = $this->context(UserRole::Admin);

        $scenario->recommendedResources()->attach($equipmentA->id, ['quantity' => 1]);

        $this->actingAs($admin)
            ->put("/app/scenarios/{$scenario->id}/equipment-template", [
                'equipment' => [
                    ['id' => $equipmentB->id, 'quantity' => 3],
                ],
            ])
            ->assertRedirect();

        $recommended = $scenario->fresh()
            ->recommendedResources()
            ->get();

        $this->assertCount(1, $recommended);
        $this->assertTrue($recommended->first()->is($equipmentB));
        $this->assertSame(3, $recommended->first()->pivot->quantity);
    }

    public function test_staff_can_view_but_cannot_change_equipment_template(): void
    {
        [$staff, $scenario, $equipment] = $this->context(UserRole::Staff);

        $this->actingAs($staff)
            ->get('/app/scenarios')
            ->assertOk();

        $this->actingAs($staff)
            ->put("/app/scenarios/{$scenario->id}/equipment-template", [
                'equipment' => [
                    ['id' => $equipment->id, 'quantity' => 2],
                ],
            ])
            ->assertForbidden();
    }

    public function test_template_rejects_room_and_cross_college_equipment(): void
    {
        [$admin, $scenario] = $this->context(UserRole::Admin);

        $room = SimResource::query()->create([
            'college_id' => $admin->college_id,
            'name' => 'SIM Lab',
            'kind' => SimResourceKind::Room,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 1,
            'is_exclusive' => true,
        ]);

        $otherCollege = College::factory()->create();
        $otherEquipment = SimResource::query()->create([
            'college_id' => $otherCollege->id,
            'name' => 'อุปกรณ์ต่างวิทยาลัย',
            'kind' => SimResourceKind::Equipment,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 5,
            'is_exclusive' => false,
        ]);

        $this->actingAs($admin)
            ->put("/app/scenarios/{$scenario->id}/equipment-template", [
                'equipment' => [
                    ['id' => $room->id, 'quantity' => 1],
                    ['id' => $otherEquipment->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHasErrors([
                'equipment.0.id',
                'equipment.1.id',
            ]);

        $this->assertCount(0, $scenario->fresh()->recommendedResources);
    }

    public function test_template_can_be_cleared(): void
    {
        [$admin, $scenario, $equipment] = $this->context(UserRole::Admin);

        $scenario->recommendedResources()->attach($equipment->id, ['quantity' => 2]);

        $this->actingAs($admin)
            ->put("/app/scenarios/{$scenario->id}/equipment-template", [
                'equipment' => [],
            ])
            ->assertRedirect();

        $this->assertCount(0, $scenario->fresh()->recommendedResources);
    }

    /**
     * @return array{User, Scenario, SimResource, SimResource}
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

        $scenario = Scenario::query()->create([
            'course_id' => $course->id,
            'name' => 'สถานการณ์จำลอง',
        ]);

        $equipmentA = SimResource::query()->create([
            'college_id' => $college->id,
            'name' => 'Patient Monitor',
            'kind' => SimResourceKind::Equipment,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 10,
            'is_exclusive' => false,
        ]);

        $equipmentB = SimResource::query()->create([
            'college_id' => $college->id,
            'name' => 'ชุดทำหัตถการ',
            'kind' => SimResourceKind::Equipment,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 8,
            'is_exclusive' => false,
        ]);

        return [$user, $scenario, $equipmentA, $equipmentB];
    }
}
