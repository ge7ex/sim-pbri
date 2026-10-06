<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Actions\CancelBookingAction;
use App\Modules\Booking\Http\Requests\CancelBookingRequest;
use App\Modules\Booking\Models\Booking;
use Illuminate\Http\RedirectResponse;

final class BookingCancellationController extends Controller
{
    public function __invoke(
        CancelBookingRequest $request,
        Booking $booking,
        CancelBookingAction $action,
    ): RedirectResponse {
        $this->authorize('cancel', $booking);

        $action->execute(
            booking: $booking,
            actor: $request->user(),
            reason: $request->validated('reason'),
        );

        return back()->with('success', 'ยกเลิกคำขอเรียบร้อยแล้ว');
    }
}
