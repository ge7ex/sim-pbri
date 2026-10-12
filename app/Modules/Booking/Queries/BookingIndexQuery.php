<?php

namespace App\Modules\Booking\Queries;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BookingIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Booking>
     */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Booking::query()
            ->with([
                'resources' => fn ($query) => $query->where('college_id', $actor->college_id)->select('sim_resources.id', 'name', 'kind'),
                'requestedBy' => fn ($query) => $query->where('college_id', $actor->college_id)->select('id', 'name'),
                'reviewedBy' => fn ($query) => $query->where('college_id', $actor->college_id)->select('id', 'name'),
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
                CarbonImmutable::parse($filters['date_from'], 'Asia/Bangkok')->startOfDay()->utc()->toDateTimeString(),
            );
        }

        if (! empty($filters['date_to'])) {
            $query->where(
                'starts_at',
                '<=',
                CarbonImmutable::parse($filters['date_to'], 'Asia/Bangkok')->endOfDay()->utc()->toDateTimeString(),
            );
        }

        return $query->paginate(20)->withQueryString();
    }
}
