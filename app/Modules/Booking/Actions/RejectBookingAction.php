<?php

namespace App\Modules\Booking\Actions;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RejectBookingAction
{
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

            if ($locked->status !== BookingStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'คำขอนี้ไม่ได้อยู่ในสถานะรอตรวจสอบ',
                ]);
            }

            $locked->forceFill([
                'status' => BookingStatus::Rejected,
                'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(),
                'review_reason' => $reason,
            ])->save();

            $locked->statusTransitions()->create([
                'from_status' => BookingStatus::Pending,
                'to_status' => BookingStatus::Rejected,
                'actor_user_id' => $actor->id,
                'reason' => $reason,
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }
}
