<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\SimResource\Actions\DeleteSimResourceAction;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ResourceDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SimResource $resource;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('room-images');
        $this->admin = User::factory()->create(['college_id' => College::factory()->create()->id, 'role' => UserRole::Admin->value]);
        $this->path = $this->admin->college_id.'/11111111-1111-4111-8111-111111111111.jpg';
        $this->resource = SimResource::create(['college_id' => $this->admin->college_id, 'name' => 'Unused room', 'kind' => 'room', 'status' => 'ready', 'quantity_total' => 1, 'is_exclusive' => true, 'capacity' => 20]);
    }

    public function test_admin_can_delete_unused_room_and_equipment_and_missing_resource_is_404(): void
    {
        foreach (['room', 'equipment'] as $kind) {
            $this->resource->update(['kind' => $kind]);
            $id = $this->resource->id;
            $this->actingAs($this->admin)->delete('/app/resources/'.$id)->assertRedirect()->assertSessionHasNoErrors();
            $this->assertDatabaseMissing('sim_resources', ['id' => $id]);
            $this->delete('/app/resources/'.$id)->assertNotFound();
            $this->resource = SimResource::create(['college_id' => $this->admin->college_id, 'name' => 'Unused equipment', 'kind' => 'equipment', 'status' => 'ready', 'quantity_total' => 1, 'is_exclusive' => false]);
        }
    }

    public function test_authorization_prevents_all_resource_and_file_mutations(): void
    {
        $this->resource->update(['image_path' => $this->path]);
        Storage::disk('room-images')->put($this->path, 'private photo');
        $actors = [
            User::factory()->create(['college_id' => $this->admin->college_id, 'role' => UserRole::Staff->value]),
            User::factory()->create(['college_id' => $this->admin->college_id, 'role' => UserRole::Lecturer->value]),
            User::factory()->create(['college_id' => College::factory()->create()->id, 'role' => UserRole::Admin->value]),
            User::factory()->create(['college_id' => null, 'role' => UserRole::Admin->value]),
        ];
        foreach ($actors as $actor) {
            $this->actingAs($actor)->delete('/app/resources/'.$this->resource->id)->assertForbidden();
            $this->assertDatabaseHas('sim_resources', ['id' => $this->resource->id, 'image_path' => $this->path]);
            Storage::disk('room-images')->assertExists($this->path);
        }
        auth()->forgetGuards();
        $this->delete('/app/resources/'.$this->resource->id)->assertRedirect('/login');
        Storage::disk('room-images')->assertExists($this->path);
    }

    public function test_every_booking_status_blocks_deletion_and_preserves_history_and_image(): void
    {
        $this->resource->update(['image_path' => $this->path]);
        Storage::disk('room-images')->put($this->path, 'private photo');
        foreach (BookingStatus::cases() as $status) {
            $booking = Booking::create(['college_id' => $this->admin->college_id, 'requested_by_user_id' => $this->admin->id, 'requester_name' => 'Test', 'starts_at' => '2026-10-08 09:00:00', 'ends_at' => '2026-10-08 10:00:00', 'status' => $status]);
            $booking->resources()->attach($this->resource->id, ['quantity' => 1]);
            $this->actingAs($this->admin)->delete('/app/resources/'.$this->resource->id)->assertSessionHasErrors('resource');
            $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => $status->value]);
            $this->assertDatabaseHas('booking_resource', ['booking_id' => $booking->id, 'sim_resource_id' => $this->resource->id]);
            Storage::disk('room-images')->assertExists($this->path);
        }
        $this->assertDatabaseCount('sim_resources', 1);
    }

    public function test_image_audit_alone_protects_resource_even_after_photo_removed(): void
    {
        DB::table('sim_resource_image_changes')->insert(['sim_resource_id' => $this->resource->id, 'actor_user_id' => $this->admin->id, 'action' => 'removed', 'created_at' => now()]);
        $this->actingAs($this->admin)->delete('/app/resources/'.$this->resource->id)->assertSessionHasErrors('resource');
        $this->assertDatabaseCount('sim_resource_image_changes', 1);
        $this->assertDatabaseCount('sim_resources', 1);
    }

    public function test_recommendation_dependency_is_protected(): void
    {
        $course = DB::table('courses')->insertGetId(['college_id' => $this->admin->college_id, 'name' => 'Course']);
        $scenario = DB::table('scenarios')->insertGetId(['course_id' => $course, 'name' => 'Scenario']);
        DB::table('scenario_resource_templates')->insert(['scenario_id' => $scenario, 'sim_resource_id' => $this->resource->id, 'quantity' => 1]);
        $this->actingAs($this->admin)->delete('/app/resources/'.$this->resource->id)->assertSessionHasErrors('resource');
        $this->assertDatabaseCount('scenario_resource_templates', 1);
        $this->assertDatabaseCount('sim_resources', 1);
    }

    public function test_invalid_or_foreign_private_path_blocks_deletion(): void
    {
        foreach (['../secret.jpg', '999/11111111-1111-4111-8111-111111111111.jpg'] as $path) {
            $this->resource->update(['image_path' => $path]);
            $this->actingAs($this->admin)->delete('/app/resources/'.$this->resource->id)->assertSessionHasErrors('resource');
            $this->assertDatabaseCount('sim_resources', 1);
        }
    }

    public function test_eligible_private_image_cleanup_runs_only_after_commit(): void
    {
        $this->resource->update(['image_path' => $this->path]);
        Storage::disk('room-images')->put($this->path, 'private photo');
        app(DeleteSimResourceAction::class)->execute($this->resource, $this->admin);
        $this->assertDatabaseMissing('sim_resources', ['id' => $this->resource->id]);
        // Laravel's test transaction manager executes callbacks after the application transaction commits.
        Storage::disk('room-images')->assertMissing($this->path);
    }

    public function test_foreign_key_race_is_safe_and_rolls_back_new_dependency(): void
    {
        // Model deleting runs after the action's exists checks, simulating a late dependency.
        SimResource::deleting(function (SimResource $resource): void {
            DB::table('sim_resource_image_changes')->insert(['sim_resource_id' => $resource->id, 'actor_user_id' => $this->admin->id, 'action' => 'uploaded', 'created_at' => now()]);
        });
        try {
            $this->actingAs($this->admin)->delete('/app/resources/'.$this->resource->id)->assertSessionHasErrors('resource');
            $this->assertDatabaseHas('sim_resources', ['id' => $this->resource->id]);
            $this->assertDatabaseCount('sim_resource_image_changes', 0);
        } finally {
            SimResource::flushEventListeners();
        }
    }

    public function test_missing_eligible_image_does_not_prevent_unused_resource_deletion(): void
    {
        $this->resource->update(['image_path' => $this->path]);
        $this->actingAs($this->admin)->delete('/app/resources/'.$this->resource->id)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sim_resources', ['id' => $this->resource->id]);
        Storage::disk('room-images')->assertMissing($this->path);
    }

    public function test_outer_rollback_preserves_resource_and_private_image(): void
    {
        $this->resource->update(['image_path' => $this->path]);
        Storage::disk('room-images')->put($this->path, 'private photo');
        DB::beginTransaction();
        app(DeleteSimResourceAction::class)->execute($this->resource, $this->admin);
        DB::rollBack();
        $this->assertDatabaseHas('sim_resources', ['id' => $this->resource->id]);
        Storage::disk('room-images')->assertExists($this->path);
    }
}
