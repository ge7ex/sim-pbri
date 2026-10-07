<?php

namespace Tests\Feature\Dashboard;

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
use App\Queries\DashboardQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_counts_and_recent_records_are_own_only_and_minimal(): void
    {
        $college = College::factory()->create();
        $actor = $this->actor($college, UserRole::Lecturer);
        $own = $this->booking($actor, BookingStatus::Pending);
        $this->booking($actor, BookingStatus::Approved);
        $this->booking($actor, BookingStatus::Cancelled);
        $other = $this->booking($this->actor($college, UserRole::Lecturer), BookingStatus::Rejected);
        $foreign = $this->booking($this->actor(College::factory()->create(), UserRole::Lecturer), BookingStatus::Pending);
        $this->actingAs($actor)->get('/app')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.summary', ['total' => 3, 'pending' => 1, 'approved' => 1, 'rejected' => 0])
            ->where('dashboard.scope_label', 'คำขอของคุณ')
            ->has('dashboard.recent_bookings', 3)
            ->where('dashboard.recent_bookings', fn ($rows) => collect($rows)->every(fn ($row) => ! in_array($row['id'], [$other->id, $foreign->id])))
            ->missing('dashboard.recent_bookings.0.note')->missing('dashboard.recent_bookings.0.requester_phone')
            ->missing('dashboard.recent_bookings.0.simulator_asset'));
        $this->assertSame($own->id, app(DashboardQuery::class)->forActor($actor)['recent_bookings'][2]['id']);
    }

    public function test_staff_and_admin_counts_include_college_workflow_but_never_other_college(): void
    {
        $college = College::factory()->create();
        $requester = $this->actor($college, UserRole::Lecturer);
        foreach (BookingStatus::cases() as $status) {
            $this->booking($requester, $status);
        }
        $this->booking($this->actor(College::factory()->create(), UserRole::Lecturer), BookingStatus::Pending);
        foreach ([UserRole::Staff, UserRole::Admin] as $role) {
            $this->actingAs($this->actor($college, $role))->get('/app')->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('dashboard.summary', ['total' => 4, 'pending' => 1, 'approved' => 1, 'rejected' => 1])
                ->where('dashboard.scope_label', 'คำขอในวิทยาลัยของคุณ')->has('dashboard.recent_bookings', 4));
        }
    }

    public function test_empty_dashboard_and_permission_boundary(): void
    {
        $actor = $this->actor(College::factory()->create(), UserRole::Lecturer);
        $this->actingAs($actor)->get('/app')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.summary', ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0])->has('dashboard.recent_bookings', 0));
        $this->post('/logout');
        $this->get('/app')->assertRedirect('/login');
        $unassigned = User::factory()->create(['college_id' => null, 'role' => null]);
        $this->actingAs($unassigned)->get('/app')->assertForbidden();
    }

    public function test_recent_limit_and_room_relationship_college_scope(): void
    {
        $actor = $this->actor(College::factory()->create(), UserRole::Lecturer);
        for ($i = 0; $i < 10; $i++) {
            $latest = $this->booking($actor, BookingStatus::Pending);
        }
        $room = SimResource::create(['college_id' => $actor->college_id, 'name' => 'Own room', 'kind' => SimResourceKind::Room, 'status' => SimResourceStatus::Ready, 'quantity_total' => 1, 'is_exclusive' => true, 'capacity' => 20]);
        $foreign = $room->replicate();
        $foreign->college_id = College::factory()->create()->id;
        $foreign->name = 'Foreign private room';
        $foreign->save();
        $latest->resources()->attach([$room->id => ['quantity' => 1], $foreign->id => ['quantity' => 1]]);
        $this->actingAs($actor)->get('/app')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.summary.total', 10)->has('dashboard.recent_bookings', 8)
            ->where('dashboard.recent_bookings.0.id', $latest->id)->where('dashboard.recent_bookings.0.rooms', ['Own room']));
    }

    public function test_foreign_scenario_is_not_projected_even_on_a_malformed_own_booking(): void
    {
        $actor = $this->actor(College::factory()->create(), UserRole::Lecturer);
        $booking = $this->booking($actor, BookingStatus::Pending);
        $course = Course::create(['college_id' => College::factory()->create()->id, 'name' => 'Private foreign course']);
        $scenario = Scenario::create(['course_id' => $course->id, 'name' => 'Private foreign scenario']);
        $booking->update(['scenario_id' => $scenario->id]);
        $this->actingAs($actor)->get('/app')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.recent_bookings.0.scenario_name', null));
    }

    private function actor(College $college, UserRole $role): User
    {
        return User::factory()->create(['college_id' => $college->id, 'role' => $role->value]);
    }

    private function booking(User $actor, BookingStatus $status): Booking
    {
        return Booking::create(['college_id' => $actor->college_id, 'requested_by_user_id' => $actor->id, 'requester_name' => $actor->name, 'requester_phone' => '0812345678', 'note' => 'PRIVATE NOTE', 'starts_at' => '2026-10-08 09:00:00', 'ends_at' => '2026-10-08 11:00:00', 'participant_count' => 10, 'status' => $status]);
    }
}
