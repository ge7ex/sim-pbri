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
                'resources:id,name,kind,status',
                'requestedBy:id,name',
            ])
            ->where('college_id', $actor->college_id)
            ->where('status', BookingStatus::Pending)
            ->oldest('created_at')
            ->paginate(20);
    }
}
