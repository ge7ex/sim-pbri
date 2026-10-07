<?php

namespace App\Queries;

use App\Core\Enums\AppPermission;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\SimResource\Enums\SimResourceKind;

final class DashboardQuery
{
    public function forActor(User $actor): array
    {
        abort_unless($actor->canAccess(AppPermission::BookingView), 403);
        $collegeId = $actor->college_id;
        $collegeWorkflow = $actor->canAccess(AppPermission::BookingApprove);
        $scope = Booking::query()->where('college_id', $collegeId)
            ->when(! $collegeWorkflow, fn ($query) => $query->where('requested_by_user_id', $actor->id));
        $counts = (clone $scope)->selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $recent = (clone $scope)->with([
            'resources' => fn ($query) => $query->where('college_id', $collegeId)->where('kind', SimResourceKind::Room)->select('sim_resources.id', 'name'),
            'scenario' => fn ($query) => $query->whereHas('course', fn ($course) => $course->where('college_id', $collegeId))->select('id', 'name'),
        ])->orderByDesc('created_at')->orderByDesc('id')->limit(8)
            ->get(['id', 'scenario_id', 'requester_name', 'starts_at', 'ends_at', 'status'])
            ->map(static fn (Booking $booking): array => [
                'id' => $booking->id,
                'requester_name' => $booking->requester_name,
                'rooms' => $booking->resources->pluck('name')->all(),
                'scenario_name' => $booking->scenario?->name,
                'starts_at' => $booking->starts_at->toIso8601String(),
                'ends_at' => $booking->ends_at->toIso8601String(),
                'status' => $booking->status->value,
            ])->all();

        return [
            'scope_label' => $collegeWorkflow ? 'คำขอในวิทยาลัยของคุณ' : 'คำขอของคุณ',
            'summary' => [
                'total' => (int) $counts->sum(),
                'pending' => (int) ($counts[BookingStatus::Pending->value] ?? 0),
                'approved' => (int) ($counts[BookingStatus::Approved->value] ?? 0),
                'rejected' => (int) ($counts[BookingStatus::Rejected->value] ?? 0),
            ],
            'recent_bookings' => $recent,
        ];
    }
}
