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

    public function test_scenario_recommendations_are_scoped_to_college_and_do_not_grant_staff_write_access(): void
    {
        [$admin, $type] = $this->context(UserRole::Admin);
        [, $foreignType] = $this->context(UserRole::Admin);
        $course = Course::query()->create(['college_id' => $admin->college_id, 'name' => 'Clinical course']);
        $scenario = Scenario::query()->create(['course_id' => $course->id, 'name' => 'Clinical scenario', 'is_active' => true]);
        $this->actingAs($admin)->put("/app/scenarios/{$scenario->id}/simulator-types", [
            'simulator_type_ids' => [$type->id],
        ])->assertRedirect();
        $this->assertSame([$type->id], $scenario->recommendedSimulatorTypes()->pluck('simulator_types.id')->all());
        $this->putJson("/app/scenarios/{$scenario->id}/simulator-types", [
            'simulator_type_ids' => [$foreignType->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('simulator_type_ids.0');
        $this->assertSame([$type->id], $scenario->recommendedSimulatorTypes()->pluck('simulator_types.id')->all());
        $staff = User::factory()->create(['college_id' => $admin->college_id, 'role' => UserRole::Staff->value]);
        $this->actingAs($staff)->put("/app/scenarios/{$scenario->id}/simulator-types", [])->assertForbidden();
    }

    public function test_duplicate_type_names_and_asset_codes_are_scoped_to_each_college(): void
    {
        [$admin, $type, $asset] = $this->context(UserRole::Admin);
        [$otherAdmin, $otherType] = $this->context(UserRole::Admin);
        $this->actingAs($admin)->postJson('/app/simulators/types', [
            'name' => $type->name, 'is_active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/app/simulators/assets', [
            ...$this->assetPayload($type), 'asset_code' => $asset->asset_code,
        ])->assertUnprocessable()->assertJsonValidationErrors('asset_code');

        $this->actingAs($admin)->post('/app/simulators/types', [
            'name' => 'Independent type', 'is_active' => true,
        ])->assertRedirect();
        $this->actingAs($otherAdmin)->post('/app/simulators/types', [
            'name' => 'Independent type', 'is_active' => true,
        ])->assertRedirect();
        $this->post('/app/simulators/assets', [
            ...$this->assetPayload($otherType), 'asset_code' => $asset->asset_code,
        ])->assertRedirect();
        $this->assertSame(2, SimulatorType::where('name', 'Independent type')->count());
        $this->assertSame(2, SimulatorAsset::where('asset_code', $asset->asset_code)->count());
    }

    public function test_financial_metadata_must_be_complete_or_entirely_absent(): void
    {
        [$admin, $type] = $this->context(UserRole::Admin);
        foreach (['purchase_year', 'purchase_price', 'useful_life_years'] as $missing) {
            $payload = $this->assetPayload($type);
            unset($payload[$missing]);
            $this->actingAs($admin)->postJson('/app/simulators/assets', $payload)
                ->assertUnprocessable()->assertJsonValidationErrors($missing);
        }
        $payload = $this->assetPayload($type);
        unset($payload['purchase_year'], $payload['purchase_price'], $payload['useful_life_years']);
        $this->post('/app/simulators/assets', $payload)->assertRedirect();
        $created = SimulatorAsset::where('asset_code', 'SIM-NEW')->sole();
        $this->assertNull($created->purchase_year);
        $this->assertNull($created->purchase_price);
        $this->assertNull($created->useful_life_years);
    }

    public function test_type_and_asset_updates_record_actor_and_before_after_values(): void
    {
        [$admin, $type, $asset] = $this->context(UserRole::Admin);
        $this->actingAs($admin)->put("/app/simulators/types/{$type->id}", [
            'name' => 'Renamed type', 'is_active' => false,
        ])->assertRedirect();
        $this->put("/app/simulators/assets/{$asset->id}", [
            ...$this->assetPayload($type), 'asset_name' => 'Renamed asset', 'status' => 'disabled',
        ])->assertRedirect();
        $typeLog = DB::table('simulator_change_logs')->where('subject_type', 'type')->where('subject_id', $type->id)->sole();
        $assetLog = DB::table('simulator_change_logs')->where('subject_type', 'asset')->where('subject_id', $asset->id)->sole();
        foreach ([$typeLog, $assetLog] as $log) {
            $this->assertSame($admin->id, (int) $log->actor_user_id);
            $this->assertSame($admin->college_id, (int) $log->college_id);
            $this->assertNotNull($log->created_at);
        }
        $this->assertSame('Clinical simulator', json_decode($typeLog->before_values, true)['name']);
        $this->assertSame('Renamed type', json_decode($typeLog->after_values, true)['name']);
        $this->assertTrue(json_decode($typeLog->before_values, true)['is_active']);
        $this->assertFalse(json_decode($typeLog->after_values, true)['is_active']);
        $this->assertSame('Training manikin', json_decode($assetLog->before_values, true)['asset_name']);
        $this->assertSame('Renamed asset', json_decode($assetLog->after_values, true)['asset_name']);
        $this->assertSame('active', json_decode($assetLog->before_values, true)['status']);
        $this->assertSame('disabled', json_decode($assetLog->after_values, true)['status']);
    }

    public function test_maintenance_history_has_no_update_or_delete_endpoint(): void
    {
        [$admin, , $asset] = $this->context(UserRole::Admin);
        $record = $asset->maintenanceRecords()->create($this->maintenancePayload() + ['created_by_user_id' => $admin->id]);
        $this->actingAs($admin)->put("/app/simulators/assets/{$asset->id}/maintenance/{$record->id}", [
            'description' => 'Rewritten history',
        ])->assertNotFound();
        $this->delete("/app/simulators/assets/{$asset->id}/maintenance/{$record->id}")->assertNotFound();
        $this->assertSame('Calibration', $record->refresh()->description);
        $this->assertDatabaseHas('simulator_maintenance_records', ['id' => $record->id]);
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
