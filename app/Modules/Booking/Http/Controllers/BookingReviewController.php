<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Actions\ApproveBookingAction;
use App\Modules\Booking\Actions\RecallBookingAction;
use App\Modules\Booking\Actions\RejectBookingAction;
use App\Modules\Booking\Http\Requests\RecallBookingRequest;
use App\Modules\Booking\Http\Requests\RejectBookingRequest;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Queries\BookingReviewQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BookingReviewController extends Controller
{
    public function index(
        Request $request,
        BookingReviewQuery $query,
    ): Response {
        return Inertia::render('Booking/Review', [
            'bookings' => $query->paginate($request->user()),
        ]);
    }

    public function approve(
        Request $request,
        Booking $booking,
        ApproveBookingAction $action,
    ): RedirectResponse {
        $this->authorize('approve', $booking);

        $action->execute($booking, $request->user());

        return back()->with('success', 'อนุมัติคำขอเรียบร้อยแล้ว');
    }

    public function reject(
        RejectBookingRequest $request,
        Booking $booking,
        RejectBookingAction $action,
    ): RedirectResponse {
        $this->authorize('reject', $booking);

        $action->execute(
            booking: $booking,
            actor: $request->user(),
            reason: $request->validated('reason'),
        );

        return back()->with('success', 'ไม่อนุมัติคำขอเรียบร้อยแล้ว');
    }

    public function recall(
        RecallBookingRequest $request,
        Booking $booking,
        RecallBookingAction $action,
    ): RedirectResponse {
        $this->authorize('recall', $booking);

        $action->execute(
            booking: $booking,
            actor: $request->user(),
            reason: $request->validated('reason'),
        );

        return back()->with('success', 'เรียกกลับคำขอเพื่อตรวจสอบใหม่แล้ว');
    }
}
