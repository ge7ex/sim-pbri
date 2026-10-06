<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ];
    }
}
