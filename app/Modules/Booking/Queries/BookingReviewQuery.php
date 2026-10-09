<?php

namespace App\Modules\Booking\Queries;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BookingReviewQuery
{
    /**
     * @return LengthAwarePaginator<int, Booking>
     */
    public function paginate(User $actor): LengthAwarePaginator
    {
        return Booking::query()
            ->with([
                'resources' => fn ($query) => $query
                    ->where('college_id', $actor->college_id)
                    ->select('sim_resources.id', 'name', 'kind', 'status'),
                'requestedBy' => fn ($query) => $query->where('college_id', $actor->college_id)->select('id', 'name'),
                'course' => fn ($query) => $query
                    ->where('college_id', $actor->college_id)
                    ->select('id', 'code', 'name'),
                'scenario' => fn ($query) => $query
                    ->whereHas('course', fn ($courseQuery) => $courseQuery->where('college_id', $actor->college_id))
                    ->select('id', 'name', 'course_id'),
                'simulatorAsset' => fn ($q) => $q->where('college_id', $actor->college_id)
                    ->select('id', 'simulator_type_id', 'asset_name', 'asset_code', 'status', 'location')
                    ->with(['simulatorType' => fn ($typeQuery) => $typeQuery->where('college_id', $actor->college_id)->select('id', 'name', 'is_active')]),
                'customEquipmentRequests:id,booking_id,name,quantity,note',
            ])
            ->where('college_id', $actor->college_id)
            ->where('status', BookingStatus::Pending)
            ->oldest('created_at')
            ->paginate(20);
    }
}
