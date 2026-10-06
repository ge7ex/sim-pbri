<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Http\Requests\BookingFilterRequest;
use App\Modules\Booking\Queries\BookingCalendarQuery;
use Inertia\Inertia;
use Inertia\Response;

final class BookingCalendarController extends Controller
{
    public function __invoke(
        BookingFilterRequest $request,
        BookingCalendarQuery $query,
    ): Response {
        return Inertia::render('Booking/Calendar', [
            'events' => $query->get(
                actor: $request->user(),
                filters: $request->validated(),
            ),
            'filters' => $request->validated(),
        ]);
    }
}
