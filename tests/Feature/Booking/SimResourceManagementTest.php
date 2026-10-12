<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SimResourceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_resources_but_cannot_create_or_update_them(): void
    {
        [$staff, $resource] = $this->context(UserRole::Staff);

        $this->actingAs($staff)->get('/app/resources')->assertOk();

        $this->actingAs($staff)
            ->post('/app/resources', $this->payload())
            ->assertForbidden();

        $this->actingAs($staff)
            ->put("/app/resources/{$resource->id}", $this->payload())
            ->assertForbidden();
    }

    public function test_admin_can_create_resource_and_college_is_derived_from_actor(): void
    {
        [$admin] = $this->context(UserRole::Admin);
        $otherCollege = College::factory()->create();

        $this->actingAs($admin)
            ->post('/app/resources', [
                ...$this->payload(),
                'college_id' => $otherCollege->id,
            ])
            ->assertRedirect();

        $resource = SimResource::query()
            ->where('name', 'SIM Lab ใหม่')
            ->sole();

        $this->assertSame($admin->college_id, $resource->college_id);
    }

    public function test_room_is_forced_to_single_exclusive_unit(): void
    {
        [$admin] = $this->context(UserRole::Admin);

        $this->actingAs($admin)
            ->post('/app/resources', [
                ...$this->payload(),
                'quantity_total' => 99,
                'is_exclusive' => false,
            ])
            ->assertRedirect();

        $resource = SimResource::query()
            ->where('name', 'SIM Lab ใหม่')
            ->sole();

        $this->assertSame(1, $resource->quantity_total);
        $this->assertTrue($resource->is_exclusive);
    }

    public function test_admin_can_assign_a_same_college_staff_member_and_save_room_master_data(): void
    {
        [$admin, $resource] = $this->context(UserRole::Admin);
        $staff = User::factory()->create([
            'college_id' => $admin->college_id,
            'role' => UserRole::Staff->value,
        ]);

        $this->actingAs($admin)->put("/app/resources/{$resource->id}", [
            ...$this->payload(), 'name' => 'Updated SIM Lab', 'building' => 'อาคารจำลอง',
            'floor' => '3', 'capacity' => 24, 'responsible_staff_user_id' => $staff->id,
        ])->assertRedirect();

        $resource->refresh();
        $this->assertSame('อาคารจำลอง', $resource->building);
        $this->assertSame('3', $resource->floor);
        $this->assertSame(24, $resource->capacity);
        $this->assertSame($staff->id, $resource->responsible_staff_user_id);
    }

    public function test_room_dimensions_are_optional_pair_and_store_metric_precision(): void
    {
        [$admin] = $this->context(UserRole::Admin);

        $this->actingAs($admin)->post('/app/resources', [
            ...$this->payload(), 'room_width_m' => '5.25', 'room_length_m' => '6.00',
        ])->assertRedirect();

        $resource = SimResource::query()->where('name', 'SIM Lab ใหม่')->sole();
        $this->assertSame('5.25', $resource->room_width_m);
        $this->assertSame('6.00', $resource->room_length_m);
    }

    public function test_lecturer_booking_room_selection_receives_saved_dimensions(): void
    {
        [$lecturer, $room] = $this->context(UserRole::Lecturer);
        $room->update(['room_width_m' => '5.25', 'room_length_m' => '6.00']);

        $this->actingAs($lecturer)->get('/app/bookings/create')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('resources.0.room_width_m', '5.25')
            ->where('resources.0.room_length_m', '6.00'));
    }

    public function test_room_dimensions_must_be_both_present_and_within_supported_range(): void
    {
        [$admin] = $this->context(UserRole::Admin);

        $this->actingAs($admin)->from('/app/resources')->post('/app/resources', [
            ...$this->payload(), 'room_width_m' => '5.25',
        ])->assertSessionHasErrors('room_length_m');

        $this->actingAs($admin)->from('/app/resources')->post('/app/resources', [
            ...$this->payload(), 'room_width_m' => '0', 'room_length_m' => '900',
        ])->assertSessionHasErrors(['room_width_m', 'room_length_m']);

        $this->assertDatabaseMissing('sim_resources', ['name' => 'SIM Lab ใหม่']);
    }

    public function test_converting_a_room_to_equipment_clears_room_dimensions(): void
    {
        [$admin, $room] = $this->context(UserRole::Admin);
        $room->update(['room_width_m' => '5.25', 'room_length_m' => '6.00']);

        $this->actingAs($admin)->put('/app/resources/'.$room->id, [
            ...$this->payload(), 'kind' => SimResourceKind::Equipment->value, 'status' => SimResourceStatus::Ready->value,
            'capacity' => null, 'room_width_m' => '5.25', 'room_length_m' => '6.00',
        ])->assertRedirect();

        $this->assertNull($room->refresh()->room_width_m);
        $this->assertNull($room->room_length_m);
    }

    public function test_responsible_person_must_be_staff_in_the_admins_college(): void
    {
        [$admin] = $this->context(UserRole::Admin);
        $foreignStaff = User::factory()->create([
            'college_id' => College::factory()->create()->id,
            'role' => UserRole::Staff->value,
        ]);

        $this->actingAs($admin)->from('/app/resources')->post('/app/resources', [
            ...$this->payload(), 'responsible_staff_user_id' => $foreignStaff->id,
        ])->assertSessionHasErrors('responsible_staff_user_id');

        $this->assertDatabaseMissing('sim_resources', ['name' => 'SIM Lab ใหม่']);
    }

    public function test_admin_cannot_update_a_room_owned_by_another_college(): void
    {
        [$admin] = $this->context(UserRole::Admin);
        $otherAdminCollege = College::factory()->create();
        $foreignRoom = SimResource::query()->create([
            'college_id' => $otherAdminCollege->id, 'name' => 'Foreign room',
            'kind' => SimResourceKind::Room, 'status' => SimResourceStatus::Ready,
            'quantity_total' => 1, 'is_exclusive' => true, 'capacity' => 20,
        ]);

        $this->actingAs($admin)->put("/app/resources/{$foreignRoom->id}", $this->payload())
            ->assertForbidden();
        $this->assertSame('Foreign room', $foreignRoom->refresh()->name);
    }

    public function test_equipment_does_not_require_room_only_fields(): void
    {
        [$admin] = $this->context(UserRole::Admin);

        $this->actingAs($admin)->post('/app/resources', [
            'name' => 'Patient monitor', 'kind' => SimResourceKind::Equipment->value,
            'status' => SimResourceStatus::Ready->value, 'quantity_total' => 5,
            'is_exclusive' => false, 'location' => null, 'description' => null,
        ])->assertRedirect();

        $equipment = SimResource::query()->where('name', 'Patient monitor')->sole();
        $this->assertSame(SimResourceKind::Equipment, $equipment->kind);
        $this->assertNull($equipment->capacity);
    }

    public function test_lecturer_cannot_view_or_manage_resource_master_data(): void
    {
        [$lecturer] = $this->context(UserRole::Lecturer);

        $this->actingAs($lecturer)->get('/app/resources')->assertForbidden();
        $this->actingAs($lecturer)->post('/app/resources', $this->payload())->assertForbidden();
    }

    /**
     * @return array{User, SimResource}
     */
    private function context(UserRole $role): array
    {
        $college = College::factory()->create();

        $user = User::factory()->create([
            'college_id' => $college->id,
            'role' => $role->value,
        ]);

        $resource = SimResource::query()->create([
            'college_id' => $college->id,
            'name' => 'SIM Lab 1',
            'kind' => SimResourceKind::Room,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 1,
            'is_exclusive' => true,
            'capacity' => 30,
        ]);

        return [$user, $resource];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'name' => 'SIM Lab ใหม่',
            'kind' => SimResourceKind::Room->value,
            'status' => SimResourceStatus::Ready->value,
            'quantity_total' => 1,
            'is_exclusive' => true,
            'location' => 'อาคาร 1 ชั้น 2',
            'description' => 'ห้องปฏิบัติการ',
            'building' => 'อาคารจำลอง',
            'floor' => '2',
            'capacity' => 40,
            'responsible_staff_user_id' => null,
        ];
    }
}
