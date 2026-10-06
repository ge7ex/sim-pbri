<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Actions\CreateBookingAction;
use App\Modules\Booking\Http\Requests\StoreBookingRequest;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BookingController extends Controller
{
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

        return Inertia::render('Booking/Create', [
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
}
