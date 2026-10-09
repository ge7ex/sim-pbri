<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class RoomDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_close_and_reopen_room_without_removing_metadata_photo_or_history(): void
    {
        [$admin, $room] = $this->context();
        $booking = $this->booking($admin, $room, BookingStatus::Approved);
        Storage::fake('room-images');
        $path = $admin->college_id.'/11111111-1111-4111-8111-111111111111.jpg';
        Storage::disk('room-images')->put($path, 'existing image');
        $room->update(['image_path' => $path]);
        DB::table('sim_resource_image_changes')->insert([
            'sim_resource_id' => $room->id, 'actor_user_id' => $admin->id,
            'action' => 'uploaded', 'created_at' => now(),
        ]);

        foreach (['disabled', 'maintenance', 'ready'] as $status) {
            $this->actingAs($admin)->put('/app/resources/'.$room->id, $this->payload($status))
                ->assertRedirect()->assertSessionHasNoErrors();
            $room->refresh();
            $this->assertSame($status, $room->status->value);
            $this->assertSame('SIM room', $room->name);
            $this->assertSame('Building A', $room->building);
            $this->assertSame(20, $room->capacity);
            $this->assertSame($path, $room->image_path);
            $this->assertSame(BookingStatus::Approved, $booking->refresh()->status);
            $this->assertDatabaseCount('booking_resource', 1);
            $this->assertDatabaseCount('sim_resource_image_changes', 1);
            Storage::disk('room-images')->assertExists($path);
        }
        $this->assertFalse(SimResourceStatus::Disabled->isBookable());
        $this->assertSame('ปิดการใช้งาน', SimResourceStatus::Disabled->label());
    }

    public function test_staff_lecturer_and_foreign_admin_cannot_close_or_reopen_room(): void
    {
        [$admin, $room] = $this->context();
        $actors = [
            User::factory()->create(['college_id' => $admin->college_id, 'role' => UserRole::Staff->value]),
            User::factory()->create(['college_id' => $admin->college_id, 'role' => UserRole::Lecturer->value]),
            User::factory()->create(['college_id' => College::factory()->create()->id, 'role' => UserRole::Admin->value]),
        ];
        foreach (['ready' => 'disabled', 'disabled' => 'ready'] as $current => $target) {
            $room->update(['status' => $current]);
            foreach ($actors as $actor) {
                $this->actingAs($actor)->put('/app/resources/'.$room->id, $this->payload($target))->assertForbidden();
                $this->assertSame($current, $room->refresh()->status->value);
            }
        }
    }

    public function test_disabled_room_is_displayed_but_cannot_be_booked_and_reopened_room_can(): void
    {
        [$admin, $room] = $this->context();
        $lecturer = User::factory()->create(['college_id' => $admin->college_id, 'role' => UserRole::Lecturer->value]);
        $this->actingAs($admin)->put('/app/resources/'.$room->id, $this->payload('disabled'))->assertSessionHasNoErrors();
        $this->actingAs($lecturer)->get('/app/bookings/create')->assertInertia(fn (Assert $page) => $page
            ->where('resources.0.id', $room->id)->where('resources.0.status', 'disabled'));
        $this->getJson('/app/resources/availability?'.http_build_query([
            'starts_at' => '2026-10-12 09:00:00', 'ends_at' => '2026-10-12 11:00:00',
        ]))->assertOk()->assertExactJson(['rooms' => []]);
        $this->post('/app/bookings', $this->bookingPayload($room))->assertSessionHasErrors('resources');
        $this->assertDatabaseCount('bookings', 0);

        $this->actingAs($admin)->put('/app/resources/'.$room->id, $this->payload('ready'))->assertSessionHasNoErrors();
        $this->actingAs($lecturer)->post('/app/bookings', $this->bookingPayload($room))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_disabled_room_blocks_approval_and_recall_without_altering_history(): void
    {
        [$admin, $room] = $this->context();
        $staff = User::factory()->create(['college_id' => $admin->college_id, 'role' => UserRole::Staff->value]);
        $pending = $this->booking($admin, $room, BookingStatus::Pending);
        $this->actingAs($admin)->put('/app/resources/'.$room->id, $this->payload('disabled'))->assertSessionHasNoErrors();
        $this->actingAs($staff)->post('/app/bookings/'.$pending->id.'/approve')->assertSessionHasErrors('resources');
        $this->assertSame(BookingStatus::Pending, $pending->refresh()->status);
        $pending->update(['status' => BookingStatus::Rejected]);
        $this->post('/app/bookings/'.$pending->id.'/recall', ['confirmed' => true, 'reason' => 'Review availability'])
            ->assertSessionHasErrors('resources');
        $this->assertSame(BookingStatus::Rejected, $pending->refresh()->status);
        $this->assertDatabaseCount('booking_status_transitions', 0);
        $this->assertDatabaseCount('booking_resource', 1);

        $this->actingAs($admin)->put('/app/resources/'.$room->id, $this->payload('ready'))->assertSessionHasNoErrors();
        $this->actingAs($staff)->post('/app/bookings/'.$pending->id.'/recall', ['confirmed' => true, 'reason' => 'Room ready'])
            ->assertSessionHasNoErrors();
        $this->assertSame(BookingStatus::Pending, $pending->refresh()->status);
    }

    public function test_disabled_is_room_only_and_equipment_keeps_existing_statuses(): void
    {
        [$admin] = $this->context();
        $payload = [...$this->payload('disabled'), 'name' => 'Equipment', 'kind' => 'equipment'];
        $this->actingAs($admin)->post('/app/resources', $payload)->assertSessionHasErrors('status');
        $this->assertDatabaseMissing('sim_resources', ['name' => 'Equipment']);
        $this->post('/app/resources', [...$payload, 'status' => 'ready'])->assertSessionHasNoErrors();
        $equipment = SimResource::where('name', 'Equipment')->sole();
        $this->put('/app/resources/'.$equipment->id, $payload)->assertSessionHasErrors('status');
        foreach (['ready', 'pending', 'maintenance'] as $status) {
            $this->put('/app/resources/'.$equipment->id, [...$payload, 'status' => $status])->assertSessionHasNoErrors();
            $this->assertSame($status, $equipment->refresh()->status->value);
        }
        $this->post('/app/resources', [...$this->payload('disabled'), 'name' => 'Closed room'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sim_resources', ['name' => 'Closed room', 'status' => 'disabled']);
    }

    private function context(): array
    {
        $admin = User::factory()->create(['college_id' => College::factory()->create()->id, 'role' => UserRole::Admin->value]);
        $room = SimResource::create([...$this->payload(), 'college_id' => $admin->college_id]);

        return [$admin, $room];
    }

    private function payload(string $status = 'ready'): array
    {
        return ['name' => 'SIM room', 'kind' => 'room', 'status' => $status, 'quantity_total' => 1,
            'is_exclusive' => true, 'capacity' => 20, 'building' => 'Building A', 'floor' => '2',
            'location' => 'Local', 'description' => 'Room metadata', 'responsible_staff_user_id' => null];
    }

    private function bookingPayload(SimResource $room): array
    {
        return ['resources' => [['id' => $room->id, 'quantity' => 1]], 'participant_count' => 10,
            'requester_phone' => '0812345678', 'starts_at' => '2026-10-12 09:00:00', 'ends_at' => '2026-10-12 11:00:00'];
    }

    private function booking(User $requester, SimResource $room, BookingStatus $status): Booking
    {
        $booking = Booking::create(['college_id' => $requester->college_id, 'requested_by_user_id' => $requester->id,
            'requester_name' => $requester->name, 'participant_count' => 10, 'requester_phone' => '0812345678',
            'starts_at' => '2026-10-12 09:00:00', 'ends_at' => '2026-10-12 11:00:00', 'status' => $status]);
        $booking->resources()->attach($room->id, ['quantity' => 1]);

        return $booking;
    }
}
