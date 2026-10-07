<?php

namespace Tests\Feature\Simulator;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\Simulator\Enums\SimulatorAssetStatus;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SimulatorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_sees_only_own_college_operational_information(): void
    {
        [$staff, $type, $asset] = $this->context(UserRole::Staff);
        $this->context(UserRole::Admin);
        $asset->maintenanceRecords()->create($this->maintenancePayload() + ['created_by_user_id' => $staff->id]);

        $this->actingAs($staff)->get('/app/simulators')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Modules/Simulator/Pages/Index')
                ->has('types', 1)
                ->where('types.0.id', $type->id)
                ->has('assets.data', 1)
                ->where('assets.data.0.id', $asset->id)
                ->missing('assets.data.0.purchase_price')
                ->missing('assets.data.0.purchase_year')
                ->missing('assets.data.0.useful_life_years')
                ->missing('assets.data.0.depreciation')
                ->has('assets.data.0.maintenance_records', 1)
                ->where('assets.data.0.maintenance_records.0.maintenance_date', '2026-10-01')
                ->where('assets.data.0.maintenance_records.0.description', 'Calibration')
                ->where('assets.data.0.maintenance_records.0.performed_by', 'Service technician')
                ->missing('assets.data.0.maintenance_records.0.cost')
                ->missing('assets.data.0.maintenance_records.0.note')
                ->missing('assets.data.0.maintenance_records.0.created_by_user_id'));
    }

    public function test_staff_cannot_mutate_types_assets_or_maintenance(): void
    {
        [$staff, $type, $asset] = $this->context(UserRole::Staff);
        $this->actingAs($staff)->post('/app/simulators/types', [])->assertForbidden();
        $this->put("/app/simulators/types/{$type->id}", [])->assertForbidden();
        $this->post('/app/simulators/assets', [])->assertForbidden();
        $this->put("/app/simulators/assets/{$asset->id}", [])->assertForbidden();
        $this->post("/app/simulators/assets/{$asset->id}/maintenance", [])->assertForbidden();
    }

    public function test_lecturer_cannot_access_management(): void
    {
        [$lecturer] = $this->context(UserRole::Lecturer);
        $this->actingAs($lecturer)->get('/app/simulators')->assertForbidden();
        $this->post('/app/simulators/types', [])->assertForbidden();
    }

    public function test_admin_can_create_update_and_record_maintenance_with_server_owned_college(): void
    {
        [$admin, $type, $asset] = $this->context(UserRole::Admin);
        $other = College::factory()->create();
        $this->actingAs($admin)->post('/app/simulators/types', [
            'name' => 'New simulator type', 'description' => 'Clinical training', 'is_active' => true,
            'college_id' => $other->id,
        ])->assertRedirect();
        $created = SimulatorType::query()->where('name', 'New simulator type')->sole();
        $this->assertSame($admin->college_id, $created->college_id);

        $this->put("/app/simulators/types/{$created->id}", [
            'name' => 'Updated simulator type', 'is_active' => false, 'college_id' => $other->id,
        ])->assertRedirect();
        $this->assertSame($admin->college_id, $created->refresh()->college_id);
        $this->assertFalse($created->is_active);

        $this->post('/app/simulators/assets', $this->assetPayload($type) + ['college_id' => $other->id])->assertRedirect();
        $newAsset = SimulatorAsset::query()->where('asset_code', 'SIM-NEW')->sole();
        $this->assertSame($admin->college_id, $newAsset->college_id);
        $this->put("/app/simulators/assets/{$newAsset->id}", [
            ...$this->assetPayload($type), 'asset_name' => 'Updated asset', 'status' => 'maintenance',
            'college_id' => $other->id,
        ])->assertRedirect();
        $this->assertSame('Updated asset', $newAsset->refresh()->asset_name);
        $this->assertSame(SimulatorAssetStatus::Maintenance, $newAsset->status);
        $this->assertSame($admin->college_id, $newAsset->college_id);

        $this->post("/app/simulators/assets/{$asset->id}/maintenance", $this->maintenancePayload() + [
            'created_by_user_id' => 999999, 'college_id' => $other->id,
        ])->assertRedirect();
        $record = $asset->maintenanceRecords()->sole();
        $this->assertSame($admin->id, $record->created_by_user_id);
        $this->assertSame('Internal service note', $record->note);
        $this->assertEquals(250.50, $record->cost);
        $this->actingAs($admin)->get('/app/simulators')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('assets.data.0.purchase_price')
                ->has('assets.data.0.maintenance_records'));
    }

    public function test_foreign_college_mutations_are_forbidden_before_payload_validation(): void
    {
        [$admin] = $this->context(UserRole::Admin);
        [, $foreignType, $foreignAsset] = $this->context(UserRole::Admin);
        $this->actingAs($admin)->put("/app/simulators/types/{$foreignType->id}", [])->assertForbidden();
        $this->put("/app/simulators/assets/{$foreignAsset->id}", [])->assertForbidden();
        $this->post("/app/simulators/assets/{$foreignAsset->id}/maintenance", [])->assertForbidden();
    }

    public function test_foreign_type_and_invalid_financial_values_are_rejected(): void
    {
        [$admin, $type, $asset] = $this->context(UserRole::Admin);
        [, $foreignType] = $this->context(UserRole::Admin);
        $this->actingAs($admin)->postJson('/app/simulators/assets', $this->assetPayload($foreignType))
            ->assertUnprocessable()->assertJsonValidationErrors('simulator_type_id');
        $this->putJson("/app/simulators/assets/{$asset->id}", $this->assetPayload($foreignType))
            ->assertUnprocessable()->assertJsonValidationErrors('simulator_type_id');
        $this->postJson('/app/simulators/assets', [
            ...$this->assetPayload($type), 'purchase_price' => -1, 'useful_life_years' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors(['purchase_price', 'useful_life_years']);
        $this->postJson("/app/simulators/assets/{$asset->id}/maintenance", [
            ...$this->maintenancePayload(), 'cost' => -1,
        ])->assertUnprocessable()->assertJsonValidationErrors('cost');
    }

    private function context(UserRole $role): array
    {
        $college = College::factory()->create();
        $user = User::factory()->create(['college_id' => $college->id, 'role' => $role->value]);
        $type = SimulatorType::query()->create(['college_id' => $college->id, 'name' => 'Clinical simulator', 'is_active' => true]);
        $asset = SimulatorAsset::query()->create([
            ...$this->assetPayload($type), 'college_id' => $college->id, 'asset_code' => 'SIM-'.$college->id,
        ]);
        return [$user, $type, $asset];
    }

    private function assetPayload(SimulatorType $type): array
    {
        return [
            'simulator_type_id' => $type->id, 'asset_name' => 'Training manikin', 'asset_code' => 'SIM-NEW',
            'purchase_year' => 2025, 'purchase_price' => 100000, 'useful_life_years' => 10,
            'status' => 'active', 'location' => 'Clinical lab', 'description' => 'Training equipment',
        ];
    }

    private function maintenancePayload(): array
    {
        return [
            'maintenance_date' => '2026-10-01', 'description' => 'Calibration', 'cost' => 250.50,
            'performed_by' => 'Service technician', 'note' => 'Internal service note',
        ];
    }
}
