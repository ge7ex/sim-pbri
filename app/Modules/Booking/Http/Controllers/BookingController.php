<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Actions\CreateBookingAction;
use App\Modules\Booking\Http\Requests\BookingFilterRequest;
use App\Modules\Booking\Http\Requests\StoreBookingRequest;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Queries\BookingIndexQuery;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BookingController extends Controller
{
    public function index(
        BookingFilterRequest $request,
        BookingIndexQuery $query,
    ): Response {
        return Inertia::render('Modules/Booking/Pages/Index', [
            'bookings' => $query->paginate(
                actor: $request->user(),
                filters: $request->validated(),
            ),
            'filters' => $request->validated(),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        $resources = SimResource::query()
            ->where('college_id', $user->college_id)
            ->where('status', SimResourceStatus::Ready)
            ->orderBy('kind')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'kind',
                'status',
                'quantity_total',
                'is_exclusive',
                'location',
                'description',
            ]);

        return Inertia::render('Modules/Booking/Pages/Create', [
            'resources' => $resources,
        ]);
    }

    public function store(
        StoreBookingRequest $request,
        CreateBookingAction $action,
    ): RedirectResponse {
        $action->execute(
            data: $request->validated(),
            actor: $request->user(),
        );

        return redirect()
            ->route('bookings.index')
            ->with('success', 'ส่งคำขอจองเรียบร้อยแล้ว');
    }

    public function show(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load([
            'resources:id,name,kind,status',
            'requestedBy:id,name',
            'reviewedBy:id,name',
            'statusTransitions.actor:id,name',
        ]);

        return Inertia::render('Modules/Booking/Pages/Show', [
            'booking' => $booking,
        ]);
    }
}
