<?php

namespace App\Modules\Booking\Policies;

use App\Core\Enums\AppPermission;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;

final class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->canAccess(AppPermission::BookingView)
            && $booking->college_id === $user->college_id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->canAccess(AppPermission::BookingCancel)
            && $booking->college_id === $user->college_id
            && $booking->requested_by_user_id === $user->id
            && in_array(
                $booking->status,
                [BookingStatus::Pending, BookingStatus::Approved],
                true,
            );
    }

    public function approve(User $user, Booking $booking): bool
    {
        return $user->canAccess(AppPermission::BookingApprove)
            && $booking->college_id === $user->college_id
            && $booking->status === BookingStatus::Pending;
    }

    public function reject(User $user, Booking $booking): bool
    {
        return $this->approve($user, $booking);
    }

    public function fillParticipantCount(User $user, Booking $booking): bool
    {
        return $user->college_id !== null
            && $user->canAccess(AppPermission::BookingApprove)
            && $booking->college_id === $user->college_id
            && $booking->participant_count === null
            && in_array($booking->status, [BookingStatus::Pending, BookingStatus::Approved, BookingStatus::Rejected], true);
    }

    public function recall(User $user, Booking $booking): bool
    {
        return $user->canAccess(AppPermission::BookingApprove)
            && $booking->college_id === $user->college_id
            && in_array(
                $booking->status,
                [BookingStatus::Approved, BookingStatus::Rejected],
                true,
            );
    }
}
