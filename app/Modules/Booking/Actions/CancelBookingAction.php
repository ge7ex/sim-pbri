<?php

namespace App\Modules\Booking\Actions;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelBookingAction
{
    public function execute(
        Booking $booking,
        User $actor,
        ?string $reason = null,
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
                [BookingStatus::Pending, BookingStatus::Approved],
                true,
            )) {
                throw ValidationException::withMessages([
                    'status' => 'คำขอนี้ไม่สามารถยกเลิกได้',
                ]);
            }

            $fromStatus = $locked->status;

            $locked->forceFill([
                'status' => BookingStatus::Cancelled,
            ])->save();

            $locked->statusTransitions()->create([
                'from_status' => $fromStatus,
                'to_status' => BookingStatus::Cancelled,
                'actor_user_id' => $actor->id,
                'reason' => $reason,
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }
}
