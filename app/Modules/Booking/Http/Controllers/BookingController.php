<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Actions\CreateBookingAction;
use App\Modules\Booking\Http\Requests\BookingFilterRequest;
use App\Modules\Booking\Http\Requests\StoreBookingRequest;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Queries\BookingIndexQuery;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\Simulator\Enums\SimulatorAssetStatus;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorType;
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
            ->where(fn ($query) => $query->where('kind', SimResourceKind::Room)->orWhere('status', SimResourceStatus::Ready))
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
                'building',
                'floor',
                'capacity',
                'image_path',
            ]);

        return Inertia::render('Modules/Booking/Pages/Create', [
            'resources' => $resources,
            'simulatorTypes' => SimulatorType::where('college_id', $user->college_id)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'simulatorAssets' => SimulatorAsset::where('college_id', $user->college_id)->where('status', SimulatorAssetStatus::Active)
                ->whereHas('simulatorType', fn ($q) => $q->where('college_id', $user->college_id)->where('is_active', true))
                ->orderBy('asset_name')->get(['id', 'simulator_type_id', 'asset_name', 'asset_code', 'status', 'location']),
            'courses' => Course::query()
                ->where('college_id', $user->college_id)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'scenarios' => Scenario::query()
                ->with([
                    'course:id,college_id,name',
                    'recommendedSimulatorTypes' => fn ($q) => $q->where('college_id', $user->college_id)->where('is_active', true)->select('simulator_types.id', 'name'),
                    'recommendedResources' => fn ($query) => $query
                        ->where('college_id', $user->college_id)
                        ->where('kind', SimResourceKind::Equipment)
                        ->select('sim_resources.id', 'name', 'kind', 'status', 'quantity_total', 'is_exclusive'),
                ])
                ->whereHas('course', fn ($query) => $query->where('college_id', $user->college_id))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'course_id', 'name', 'description']),
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
            'resources' => fn ($query) => $query
                ->where('college_id', $booking->college_id)
                ->select('sim_resources.id', 'name', 'kind', 'status'),
            'course' => fn ($query) => $query
                ->where('college_id', $booking->college_id)
                ->select('id', 'code', 'name'),
            'scenario' => fn ($query) => $query
                ->whereHas('course', fn ($courseQuery) => $courseQuery->where('college_id', $booking->college_id))
                ->select('id', 'name', 'course_id'),
            'simulatorAsset' => fn ($q) => $q->where('college_id', $booking->college_id)
                ->select('id', 'simulator_type_id', 'asset_name', 'asset_code', 'status', 'location')
                ->with(['simulatorType' => fn ($typeQuery) => $typeQuery->where('college_id', $booking->college_id)->select('id', 'name', 'is_active')]),
            'customEquipmentRequests:id,booking_id,name,quantity,note',
            'requestedBy:id,name',
            'reviewedBy:id,name',
            'statusTransitions.actor:id,name',
            'participantAmendments.actor:id,name',
        ]);

        return Inertia::render('Modules/Booking/Pages/Show', [
            'booking' => $booking,
            'canFillParticipantCount' => request()->user()->can('fillParticipantCount', $booking),
        ]);
    }
}
