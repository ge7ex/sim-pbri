<?php

namespace App\Modules\Booking\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function blocksCalendar(): bool
    {
        return match ($this) {
            self::Pending, self::Approved => true,
            self::Rejected, self::Cancelled => false,
        };
    }
}
