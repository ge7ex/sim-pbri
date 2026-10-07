<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Actions\FillParticipantCountAction;
use App\Modules\Booking\Http\Requests\FillParticipantCountRequest;
use App\Modules\Booking\Models\Booking;
use Illuminate\Http\RedirectResponse;

final class BookingParticipantController extends Controller
{
    public function __invoke(FillParticipantCountRequest $request, Booking $booking, FillParticipantCountAction $action): RedirectResponse
    {
        $action->execute($booking, $request->user(), (int) $request->validated('participant_count'), $request->validated('reason'));

        return back()->with('success', 'บันทึกจำนวนผู้เข้าใช้งานและประวัติการแก้ไขแล้ว');
    }
}
