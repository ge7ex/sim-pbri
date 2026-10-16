<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Http\Requests\BookingFilterRequest;
use App\Modules\Booking\Queries\BookingCalendarQuery;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

final class BookingCalendarController extends Controller
{
    public function __invoke(
        BookingFilterRequest $request,
        BookingCalendarQuery $query,
    ): Response {
        $filters = $request->validated();
        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $today = CarbonImmutable::now('Asia/Bangkok');
            $filters['date_from'] = $today->startOfMonth()->toDateString();
            $filters['date_to'] = $today->endOfMonth()->toDateString();
        }

        return Inertia::render('Modules/Booking/Pages/Calendar', [
            'events' => $query->get(
                actor: $request->user(),
                filters: $filters,
            ),
            'filters' => $filters,
        ]);
    }
}
