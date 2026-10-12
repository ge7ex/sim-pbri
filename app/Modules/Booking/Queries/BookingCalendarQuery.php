<?php

namespace App\Modules\Booking\Queries;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Carbon\CarbonImmutable;

final class BookingCalendarQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function get(User $actor, array $filters): array
    {
        $query = Booking::query()
            ->with(['resources' => fn ($query) => $query->where('college_id', $actor->college_id)->select('sim_resources.id', 'name', 'kind')])
            ->where(function ($query) use ($actor): void {
                $query
                    ->where('college_id', $actor->college_id)
                    ->orWhere(function ($query): void {
                        $query->whereIn('status', [
                            BookingStatus::Pending,
                            BookingStatus::Approved,
                        ]);
                    });
            })
            ->orderBy('starts_at')
            ->limit(500);

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

        return $query
            ->get()
            ->map(function (Booking $booking) use ($actor): array {
                if ($booking->college_id !== $actor->college_id) {
                    return [
                        'starts_at' => $booking->starts_at->toIso8601String(),
                        'ends_at' => $booking->ends_at->toIso8601String(),
                        'title' => 'ถูกจองแล้ว',
                    ];
                }

                return [
                    'id' => $booking->id,
                    'starts_at' => $booking->starts_at->toIso8601String(),
                    'ends_at' => $booking->ends_at->toIso8601String(),
                    'title' => $booking->requester_name,
                    'status' => $booking->status->value,
                    'resources' => $booking->resources
                        ->map(static fn ($resource): array => [
                            'id' => $resource->id,
                            'name' => $resource->pivot->resource_name_snapshot ?? $resource->name,
                            'is_historical_snapshot' => $resource->pivot->resource_name_snapshot !== null,
                            'kind' => $resource->kind->value,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }
}
