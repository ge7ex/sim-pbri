<?php

namespace App\Modules\Booking\Actions;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingAvailabilityResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecallBookingAction
{
    public function __construct(
        private readonly BookingAvailabilityResolver $availabilityResolver,
    ) {
    }

    public function execute(
        Booking $booking,
        User $actor,
        string $reason,
    ): Booking {
        return DB::transaction(function () use (
            $booking,
            $actor,
            $reason,
        ): Booking {
            $locked = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array(
                $locked->status,
                [BookingStatus::Approved, BookingStatus::Rejected],
                true,
            )) {
                throw ValidationException::withMessages([
                    'status' => 'รายการนี้ไม่สามารถเรียกกลับเพื่อตรวจสอบใหม่ได้',
                ]);
            }

            $fromStatus = $locked->status;

            $resources = $locked->resources()
                ->orderBy('sim_resources.id')
                ->lockForUpdate()
                ->get();

            $quantities = $resources
                ->mapWithKeys(
                    static fn ($resource): array => [
                        $resource->id => (int) $resource->pivot->quantity,
                    ],
                )
                ->all();

            $this->availabilityResolver->ensureAvailable(
                resources: $resources,
                requestedQuantities: $quantities,
                startsAt: $locked->starts_at,
                endsAt: $locked->ends_at,
                ignoreBookingId: $locked->id,
            );

            $locked->forceFill([
                'status' => BookingStatus::Pending,
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
                'review_reason' => null,
            ])->save();

            $locked->statusTransitions()->create([
                'from_status' => $fromStatus,
                'to_status' => BookingStatus::Pending,
                'actor_user_id' => $actor->id,
                'reason' => $reason,
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }
}
