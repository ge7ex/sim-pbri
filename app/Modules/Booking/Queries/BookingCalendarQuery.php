<?php

namespace App\Modules\Booking\Queries;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;

final class BookingCalendarQuery
{
    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function get(User $actor, array $filters): array
    {
        $query = Booking::query()
            ->with('resources:id,name,kind')
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
                            'name' => $resource->name,
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
