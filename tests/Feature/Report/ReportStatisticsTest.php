<?php

namespace Tests\Feature\Report;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Report\Queries\ReportStatisticsQuery;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorMaintenanceRecord;
use App\Modules\Simulator\Models\SimulatorType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ReportStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_authorization_and_zero_state(): void
    {
        $this->get('/app/reports')->assertRedirect('/login');
        $college = College::factory()->create();
        $this->actingAs($this->actor($college, UserRole::Lecturer))->get('/app/reports')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'staff', 'college_id' => null]))->get('/app/reports')->assertForbidden();
        foreach ([UserRole::Staff, UserRole::Admin] as $role) {
            $this->actingAs($this->actor($college, $role))->get('/app/reports?date_from=2026-10-01&date_to=2026-10-03')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Modules/Report/Pages/Index', false)->where('statistics.workflow', ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0])->has('statistics.trend', 3)->where('statistics.participants', ['total' => 0, 'average' => null, 'known_count' => 0, 'missing_count' => 0])->has('statistics.rooms', 0));
        }
    }

    public function test_approved_usage_quantity_participants_and_clipped_overlap(): void
    {
        $actor = $this->actor(College::factory()->create(), UserRole::Staff);
        $room = $this->resource($actor, 'room');
        $equipment = $this->resource($actor, 'equipment');
        $course = Course::create(['college_id' => $actor->college_id, 'name' => 'Course']);
        $scenario = Scenario::create(['course_id' => $course->id, 'name' => 'Scenario']);
        $type = SimulatorType::create(['college_id' => $actor->college_id, 'name' => 'Type']);
        $asset = SimulatorAsset::create(['college_id' => $actor->college_id, 'simulator_type_id' => $type->id, 'asset_name' => 'Asset', 'asset_code' => 'A1']);
        $first = $this->booking($actor, 'approved', ['starts_at' => '2026-09-30 23:00:00', 'ends_at' => '2026-10-01 02:00:00', 'course_id' => $course->id, 'scenario_id' => $scenario->id, 'simulator_asset_id' => $asset->id]);
        $first->resources()->attach([$room->id => ['quantity' => 1], $equipment->id => ['quantity' => 3]]);
        $second = $this->booking($actor, 'approved', ['starts_at' => '2026-10-01 23:00:00', 'ends_at' => '2026-10-02 02:00:00', 'participant_count' => null]);
        $second->resources()->attach([$room->id => ['quantity' => 1], $equipment->id => ['quantity' => 2]]);
        foreach (['pending', 'rejected', 'cancelled'] as $status) {
            $booking = $this->booking($actor, $status);
            $booking->resources()->attach($equipment->id, ['quantity' => 9]);
        }
        $this->booking($actor, 'approved', ['starts_at' => '2026-09-30 20:00:00', 'ends_at' => '2026-10-01 00:00:00']);
        $this->booking($actor, 'approved', ['starts_at' => '2026-10-02 00:00:00', 'ends_at' => '2026-10-02 01:00:00']);
        $data = $this->report($actor);
        $this->assertSame(['total' => 5, 'pending' => 1, 'approved' => 2, 'rejected' => 1, 'cancelled' => 1], $data['statistics']['workflow']);
        $this->assertSame(3.0, $data['statistics']['rooms'][0]['hours']);
        $this->assertSame(2, $data['statistics']['rooms'][0]['bookings']);
        $this->assertSame(50.0, $data['statistics']['rooms'][0]['average_capacity_percent']);
        $this->assertSame(5, $data['statistics']['equipment'][0]['quantity']);
        $this->assertSame(2.0, $data['statistics']['simulators'][0]['hours']);
        $this->assertSame(1, $data['statistics']['courses'][0]['bookings']);
        $this->assertSame('Course', $data['statistics']['scenarios'][0]['course_name']);
        $this->assertSame(['total' => 10, 'average' => 10.0, 'known_count' => 1, 'missing_count' => 1], $data['statistics']['participants']);
        $this->assertSame(['date' => '2026-10-01', 'total' => 5, 'approved' => 2], $data['statistics']['trend'][0]);
        $pending = $this->report($actor, ['status' => 'pending']);
        $this->assertSame(1, $pending['statistics']['workflow']['total']);
        $this->assertSame([], $pending['statistics']['equipment']);
        $this->assertSame(1, $this->report($actor, ['simulator_asset_id' => $asset->id])['statistics']['workflow']['total']);
        $this->assertSame(2, $this->report($actor, ['room_id' => $room->id])['statistics']['workflow']['total']);
        $this->assertSame(1, $this->report($actor, ['course_id' => $course->id, 'scenario_id' => $scenario->id])['statistics']['workflow']['total']);
    }

    public function test_college_totals_options_and_malformed_relationships_are_scoped(): void
    {
        $own = $this->actor(College::factory()->create(), UserRole::Staff);
        $foreign = $this->actor(College::factory()->create(), UserRole::Staff);
        $room = $this->resource($foreign, 'room');
        $equipment = $this->resource($foreign, 'equipment');
        $course = Course::create(['college_id' => $foreign->college_id, 'name' => 'FOREIGN_SECRET']);
        $scenario = Scenario::create(['course_id' => $course->id, 'name' => 'FOREIGN_SECRET']);
        $type = SimulatorType::create(['college_id' => $foreign->college_id, 'name' => 'FOREIGN_SECRET']);
        $foreignAsset = SimulatorAsset::create(['college_id' => $foreign->college_id, 'simulator_type_id' => $type->id, 'asset_name' => 'FOREIGN_SECRET', 'asset_code' => 'F1']);
        $malformed = $this->booking($own, 'approved', ['course_id' => $course->id, 'scenario_id' => $scenario->id, 'simulator_asset_id' => $foreignAsset->id]);
        $malformed->resources()->attach([$room->id => ['quantity' => 1], $equipment->id => ['quantity' => 2]]);
        $this->booking($foreign, 'approved');
        $data = $this->report($own);
        $this->assertSame(1, $data['statistics']['workflow']['total']);
        foreach (['rooms', 'equipment', 'courses', 'scenarios'] as $key) {
            $this->assertSame([], $data['statistics'][$key]);
        }
        $this->assertSame([], $data['statistics']['simulators']);
        $this->assertStringNotContainsString('FOREIGN_SECRET', json_encode($data));
        foreach (['room_id' => $room->id, 'simulator_asset_id' => $foreignAsset->id, 'course_id' => $course->id, 'scenario_id' => $scenario->id] as $key => $value) {
            $this->actingAs($own)->get('/app/reports?'.$key.'='.$value)->assertSessionHasErrors($key);
        }
        $this->assertSame(1, $this->actingAs($own)->get('/app/reports?college_id='.$foreign->college_id.'&date_from=2026-10-01&date_to=2026-10-01')->assertOk()->inertiaProps('statistics.workflow.total'));
    }

    public function test_date_and_status_validation_and_default_month(): void
    {
        $actor = $this->actor(College::factory()->create(), UserRole::Staff);
        $this->actingAs($actor);
        $this->get('/app/reports?date_from=2026-10-02&date_to=2026-10-01')->assertSessionHasErrors('date_to');
        $this->get('/app/reports?date_from=2026-01-01&date_to=2027-01-02')->assertSessionHasErrors('date_to');
        $this->get('/app/reports?date_from=nope&status=bogus')->assertSessionHasErrors(['date_from', 'status']);
        $this->travelTo(CarbonImmutable::parse('2026-10-15 23:30:00', config('app.timezone')));
        $this->get('/app/reports')->assertOk()->assertInertia(fn (Assert $page) => $page->where('filters.date_from', '2026-10-01')->where('filters.date_to', '2026-10-31'));
        $this->get('/app/reports?date_from=2026-01-01&date_to=2027-01-01')->assertOk()->assertInertia(fn (Assert $page) => $page->has('statistics.trend', 366));
        $this->travelBack();
    }

    public function test_staff_never_queries_financial_columns_admin_reuses_depreciation(): void
    {
        $college = College::factory()->create();
        $staff = $this->actor($college, UserRole::Staff);
        $admin = $this->actor($college, UserRole::Admin);
        $type = SimulatorType::create(['college_id' => $college->id, 'name' => 'Type']);
        $asset = SimulatorAsset::create(['college_id' => $college->id, 'simulator_type_id' => $type->id, 'asset_name' => 'Asset', 'asset_code' => 'A1', 'purchase_year' => 2024, 'purchase_price' => '1000.00', 'useful_life_years' => 5]);
        SimulatorMaintenanceRecord::create(['simulator_asset_id' => $asset->id, 'maintenance_date' => '2026-10-01', 'description' => 'PRIVATE FINANCIAL NOTE', 'cost' => '120.50', 'created_by_user_id' => $admin->id]);
        SimulatorMaintenanceRecord::create(['simulator_asset_id' => $asset->id, 'maintenance_date' => '2026-09-01', 'description' => 'Outside', 'cost' => '999.00', 'created_by_user_id' => $admin->id]);
        DB::enableQueryLog();
        $data = $this->report($staff);
        $this->assertArrayNotHasKey('assetStatistics', $data);
        $queries = json_encode(DB::getQueryLog());
        foreach (['purchase_price', 'useful_life_years', 'maintenance_records', 'cost'] as $key) {
            $this->assertStringNotContainsString($key, $queries);
        }
        DB::disableQueryLog();
        $this->travelTo(CarbonImmutable::parse('2026-10-09'));
        $financial = $this->report($admin)['assetStatistics'];
        $this->assertSame('1000.00', $financial['purchase_value']);
        $this->assertSame('600.00', $financial['current_value']);
        $this->assertSame('120.50', $financial['maintenance_cost']);
        $this->assertSame(1, $financial['maintenance_events']);
        $this->assertStringNotContainsString('PRIVATE FINANCIAL NOTE', json_encode($financial));
        $this->actingAs($staff)->get('/app/reports')->assertOk()->assertInertia(fn (Assert $page) => $page->missing('assetStatistics'));
        $this->travelBack();
    }

    public function test_admin_snapshot_ignores_booking_filters_and_excludes_foreign_assets_and_costs(): void
    {
        $admin = $this->actor(College::factory()->create(), UserRole::Admin);
        $foreign = $this->actor(College::factory()->create(), UserRole::Admin);
        foreach ([$admin, $foreign] as $actor) {
            $type = SimulatorType::create(['college_id' => $actor->college_id, 'name' => 'Type']);
            $asset = SimulatorAsset::create(['college_id' => $actor->college_id, 'simulator_type_id' => $type->id, 'asset_name' => 'Asset', 'asset_code' => 'A1', 'status' => 'maintenance']);
            SimulatorMaintenanceRecord::create(['simulator_asset_id' => $asset->id, 'maintenance_date' => '2026-10-01', 'description' => 'Maintenance', 'cost' => $actor === $admin ? '25.00' : '99000.00', 'created_by_user_id' => $actor->id]);
        }
        $data = $this->report($admin, ['status' => 'pending'])['assetStatistics'];
        $this->assertSame(1, $data['total']);
        $this->assertSame(['maintenance' => 1], $data['status_counts']);
        $this->assertSame(1, $data['maintenance_events']);
        $this->assertSame('25.00', $data['maintenance_cost']);
        $this->assertSame('0.00', $data['purchase_value']);
        $this->assertSame('0.00', $data['current_value']);
        $this->assertSame(1, $data['unvalued_count']);
    }

    public function test_local_operational_date_boundaries_do_not_shift_with_server_clock(): void
    {
        config(['app.timezone' => 'Asia/Bangkok']);
        $actor = $this->actor(College::factory()->create(), UserRole::Staff);
        $this->booking($actor, 'approved', ['starts_at' => '2026-10-01 00:00:00', 'ends_at' => '2026-10-01 01:00:00']);
        $this->booking($actor, 'approved', ['starts_at' => '2026-10-01 23:00:00', 'ends_at' => '2026-10-02 00:00:00']);
        $this->booking($actor, 'approved', ['starts_at' => '2026-10-02 00:00:00', 'ends_at' => '2026-10-02 01:00:00']);
        $data = $this->report($actor);
        $this->assertSame(2, $data['statistics']['workflow']['approved']);
        $this->assertSame(['date' => '2026-10-01', 'total' => 2, 'approved' => 2], $data['statistics']['trend'][0]);
    }

    public function test_last_used_timestamps_include_timezone_for_the_shared_booking_display(): void
    {
        $previousTimezone = date_default_timezone_get();
        try {
            foreach (['UTC', 'Asia/Bangkok'] as $timezone) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
                $actor = $this->actor(College::factory()->create(), UserRole::Staff);
                $room = $this->resource($actor, 'room');
                $type = SimulatorType::create(['college_id' => $actor->college_id, 'name' => 'Type']);
                $asset = SimulatorAsset::create(['college_id' => $actor->college_id, 'simulator_type_id' => $type->id, 'asset_name' => 'Asset']);
                $booking = $this->booking($actor, 'approved', ['simulator_asset_id' => $asset->id]);
                $booking->resources()->attach($room->id, ['quantity' => 1]);
                $data = $this->report($actor)['statistics'];
                $expected = CarbonImmutable::parse('2026-10-01 09:00:00', $timezone)->toISOString();
                $this->assertSame($expected, $data['rooms'][0]['last_used_at']);
                $this->assertSame($expected, $data['simulators'][0]['last_used_at']);
                $this->assertSame(2.0, $data['rooms'][0]['hours']);
            }
        } finally {
            date_default_timezone_set($previousTimezone);
        }
    }

    private function actor(College $college, UserRole $role): User
    {
        return User::factory()->create(['college_id' => $college->id, 'role' => $role->value]);
    }

    private function resource(User $actor, string $kind): SimResource
    {
        return SimResource::create(['college_id' => $actor->college_id, 'name' => 'Resource '.$kind, 'kind' => $kind, 'status' => 'ready', 'quantity_total' => 10, 'capacity' => $kind === 'room' ? 20 : null, 'is_exclusive' => $kind === 'room']);
    }

    private function booking(User $actor, string $status, array $extra = []): Booking
    {
        return Booking::create(array_merge(['college_id' => $actor->college_id, 'requested_by_user_id' => $actor->id, 'requester_name' => $actor->name, 'requester_phone' => '0812345678', 'starts_at' => '2026-10-01 09:00:00', 'ends_at' => '2026-10-01 11:00:00', 'participant_count' => 10, 'status' => $status], $extra));
    }

    private function report(User $actor, array $extra = []): array
    {
        return app(ReportStatisticsQuery::class)->forActor($actor, array_merge(['date_from' => '2026-10-01', 'date_to' => '2026-10-01', 'status' => null, 'room_id' => null, 'simulator_asset_id' => null, 'course_id' => null, 'scenario_id' => null], $extra));
    }
}
