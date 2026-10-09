<?php

namespace App\Modules\Report\Queries;

use App\Core\Enums\AppPermission;
use App\Core\Enums\UserRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class ReportStatisticsQuery
{
    public function __construct(private AdminAssetStatisticsQuery $assets) {}

    public function forActor(User $actor, array $filters): array
    {
        abort_unless($actor->canAccess(AppPermission::ReportView), 403);
        $college = $actor->college_id;
        $from = CarbonImmutable::parse($filters['date_from'], config('app.timezone'))->startOfDay();
        $until = CarbonImmutable::parse($filters['date_to'], config('app.timezone'))->addDay()->startOfDay();
        $fromSql = $from->toDateTimeString();
        $untilSql = $until->toDateTimeString();
        $scope = DB::table('bookings as b')->where('b.college_id', $college)
            ->where('b.starts_at', '<', $untilSql)->where('b.ends_at', '>', $fromSql);
        if ($filters['status']) {
            $scope->where('b.status', $filters['status']);
        }
        foreach (['simulator_asset_id', 'course_id', 'scenario_id'] as $field) {
            if ($filters[$field]) {
                $scope->where('b.'.$field, $filters[$field]);
            }
        }
        if ($filters['room_id']) {
            $scope->whereExists(fn (Builder $q) => $q->selectRaw('1')->from('booking_resource as br')->whereColumn('br.booking_id', 'b.id')->where('br.sim_resource_id', $filters['room_id']));
        }
        $counts = (clone $scope)->selectRaw('b.status, COUNT(*) AS total')->groupBy('b.status')->pluck('total', 'status');
        $workflow = ['total' => (int) $counts->sum()];
        foreach (['pending', 'approved', 'rejected', 'cancelled'] as $status) {
            $workflow[$status] = (int) ($counts[$status] ?? 0);
        }
        $approved = (clone $scope)->where('b.status', 'approved');
        // Bind range boundaries; DATE works on both supported MySQL and isolated SQLite.
        $trendRows = (clone $scope)->selectRaw('DATE(CASE WHEN b.starts_at < ? THEN ? ELSE b.starts_at END) AS date, COUNT(*) AS total, SUM(CASE WHEN b.status = ? THEN 1 ELSE 0 END) AS approved', [$fromSql, $fromSql, 'approved'])->groupBy('date')->orderBy('date')->get()->keyBy('date');
        $trend = [];
        for ($date = $from; $date->lt($until); $date = $date->addDay()) {
            $key = $date->toDateString();
            $row = $trendRows->get($key);
            $trend[] = ['date' => $key, 'total' => (int) ($row->total ?? 0), 'approved' => (int) ($row->approved ?? 0)];
        }
        $start = 'CASE WHEN b.starts_at < ? THEN ? ELSE b.starts_at END';
        $end = 'CASE WHEN b.ends_at > ? THEN ? ELSE b.ends_at END';
        $hours = DB::getDriverName() === 'sqlite' ? "SUM((julianday($end) - julianday($start)) * 24)" : "SUM(TIMESTAMPDIFF(SECOND, $start, $end) / 3600.0)";
        $hoursBindings = DB::getDriverName() === 'sqlite' ? [$untilSql, $untilSql, $fromSql, $fromSql] : [$fromSql, $fromSql, $untilSql, $untilSql];
        $rooms = (clone $approved)->join('booking_resource as br', 'br.booking_id', '=', 'b.id')->join('sim_resources as r', 'r.id', '=', 'br.sim_resource_id')->where('r.college_id', $college)->where('r.kind', 'room')
            ->selectRaw('r.id, r.name, r.capacity, COUNT(*) AS bookings, COALESCE(SUM(b.participant_count), 0) AS participants, AVG(b.participant_count) AS average_participants, MAX(b.starts_at) AS last_used_at')->selectRaw($hours.' AS hours', $hoursBindings)->groupBy('r.id', 'r.name', 'r.capacity')->orderByDesc('bookings')->orderBy('r.id')->limit(10)->get()->map(fn ($row) => [
                'id' => $row->id, 'name' => $row->name, 'capacity' => $row->capacity === null ? null : (int) $row->capacity,
                'bookings' => (int) $row->bookings, 'hours' => round((float) $row->hours, 2), 'participants' => (int) $row->participants,
                'average_participants' => $row->average_participants === null ? null : round((float) $row->average_participants, 2),
                'average_capacity_percent' => $row->capacity > 0 && $row->average_participants !== null ? round($row->average_participants / $row->capacity * 100, 2) : null,
                'last_used_at' => CarbonImmutable::parse($row->last_used_at, config('app.timezone'))->toISOString(),
            ])->all();
        $simulators = (clone $approved)->join('simulator_assets as a', 'a.id', '=', 'b.simulator_asset_id')->leftJoin('simulator_types as t', fn ($join) => $join->on('t.id', '=', 'a.simulator_type_id')->where('t.college_id', $college))->where('a.college_id', $college)
            ->selectRaw('a.id, a.asset_name AS name, t.name AS type_name, a.status, COUNT(*) AS bookings, MAX(b.starts_at) AS last_used_at')->selectRaw($hours.' AS hours', $hoursBindings)->groupBy('a.id', 'a.asset_name', 't.name', 'a.status')->orderByDesc('bookings')->orderBy('a.id')->limit(10)->get()->map(fn ($row) => ['id' => $row->id, 'name' => $row->name, 'type_name' => $row->type_name, 'status' => $row->status, 'bookings' => (int) $row->bookings, 'hours' => round((float) $row->hours, 2), 'last_used_at' => CarbonImmutable::parse($row->last_used_at, config('app.timezone'))->toISOString()])->all();
        $equipment = (clone $approved)->join('booking_resource as br', 'br.booking_id', '=', 'b.id')->join('sim_resources as r', 'r.id', '=', 'br.sim_resource_id')->where('r.college_id', $college)->where('r.kind', 'equipment')->selectRaw('r.id, r.name, COUNT(*) AS bookings, SUM(br.quantity) AS quantity')->groupBy('r.id', 'r.name')->orderByDesc('quantity')->orderBy('r.id')->limit(10)->get()->map(fn ($row) => ['id' => $row->id, 'name' => $row->name, 'bookings' => (int) $row->bookings, 'quantity' => (int) $row->quantity])->all();
        $courses = (clone $approved)->join('courses as c', 'c.id', '=', 'b.course_id')->where('c.college_id', $college)->selectRaw('c.id, c.name, COUNT(*) AS bookings, COALESCE(SUM(b.participant_count),0) AS participants')->groupBy('c.id', 'c.name')->orderByDesc('bookings')->orderBy('c.id')->limit(10)->get()->map(fn ($row) => ['id' => $row->id, 'name' => $row->name, 'bookings' => (int) $row->bookings, 'participants' => (int) $row->participants])->all();
        $scenarios = (clone $approved)->join('scenarios as s', 's.id', '=', 'b.scenario_id')->join('courses as c', 'c.id', '=', 's.course_id')->where('c.college_id', $college)->selectRaw('s.id, s.name, c.name AS course_name, COUNT(*) AS bookings, COALESCE(SUM(b.participant_count),0) AS participants')->groupBy('s.id', 's.name', 'c.name')->orderByDesc('bookings')->orderBy('s.id')->limit(10)->get()->map(fn ($row) => ['id' => $row->id, 'name' => $row->name, 'course_name' => $row->course_name, 'bookings' => (int) $row->bookings, 'participants' => (int) $row->participants])->all();
        $participants = (clone $approved)->selectRaw('COALESCE(SUM(b.participant_count),0) AS total, AVG(b.participant_count) AS average, COUNT(b.participant_count) AS known_count, COUNT(*) - COUNT(b.participant_count) AS missing_count')->first();
        $result = ['filters' => $filters, 'filterOptions' => [
            'rooms' => DB::table('sim_resources')->where('college_id', $college)->where('kind', 'room')->orderBy('name')->get(['id', 'name'])->all(),
            'simulators' => DB::table('simulator_assets')->where('college_id', $college)->orderBy('asset_name')->get(['id', 'asset_name as name'])->all(),
            'courses' => DB::table('courses')->where('college_id', $college)->orderBy('name')->get(['id', 'name'])->all(),
            'scenarios' => DB::table('scenarios as s')->join('courses as c', 'c.id', '=', 's.course_id')->where('c.college_id', $college)->orderBy('s.name')->get(['s.id', 's.name', 's.course_id'])->all(),
        ], 'statistics' => ['workflow' => $workflow, 'trend' => $trend, 'rooms' => $rooms, 'simulators' => $simulators, 'equipment' => $equipment, 'courses' => $courses, 'scenarios' => $scenarios, 'participants' => [
            'total' => (int) $participants->total, 'average' => $participants->average === null ? null : round((float) $participants->average, 2), 'known_count' => (int) $participants->known_count, 'missing_count' => (int) $participants->missing_count,
        ]]];
        if ($actor->accessRole() === UserRole::Admin) {
            $result['assetStatistics'] = $this->assets->forCollege($college, $filters);
        }

        return $result;
    }
}
