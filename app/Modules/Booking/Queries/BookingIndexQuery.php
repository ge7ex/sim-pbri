<?php

namespace App\Modules\Booking\Queries;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BookingIndexQuery
{
    /**
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Booking>
     */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Booking::query()
            ->with([
                'resources:id,name,kind',
                'requestedBy:id,name',
                'reviewedBy:id,name',
            ])
            ->where('college_id', $actor->college_id)
            ->latest('starts_at');

        $status = BookingStatus::tryFrom((string) ($filters['status'] ?? ''));

        if ($status !== null) {
            $query->where('status', $status);
        }

        if (! empty($filters['date_from'])) {
            $query->where(
                'ends_at',
                '>=',
                $filters['date_from'].' 00:00:00',
            );
        }

        if (! empty($filters['date_to'])) {
            $query->where(
                'starts_at',
                '<=',
                $filters['date_to'].' 23:59:59',
            );
        }

        return $query->paginate(20)->withQueryString();
    }
}
