<?php

namespace App\Modules\SimResource\Http\Controllers;

use App\Core\Enums\AppPermission;
use App\Core\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Booking\Services\BookingAvailabilityResolver;
use App\Modules\SimResource\Actions\DeleteSimResourceAction;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Http\Requests\SimulatorRoomAvailabilityRequest;
use App\Modules\SimResource\Http\Requests\StoreSimResourceRequest;
use App\Modules\SimResource\Http\Requests\UpdateSimResourceRequest;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\SimResource\Services\RoomImageStorage;
use App\Modules\SimResource\Services\SaveResourceWithImage;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class SimResourceController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $request->user();
        $resources = SimResource::query()
            ->where('college_id', $actor->college_id)
            ->with(['responsibleStaff' => fn ($query) => $query->where('college_id', $actor->college_id)->select('id', 'name')])
            ->orderBy('kind')
            ->orderBy('name')
            ->get();

        return Inertia::render('Modules/SimResource/Pages/Index', [
            'resources' => $resources,
            'responsibleStaff' => $actor->canAccess(AppPermission::ResourceUpdate)
                ? User::query()->where('college_id', $actor->college_id)->where('role', UserRole::Staff->value)->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }

    public function roomAvailability(SimulatorRoomAvailabilityRequest $request, BookingAvailabilityResolver $resolver): JsonResponse
    {
        $collegeId = $request->user()->college_id;
        $ids = SimResource::query()->where('college_id', $collegeId)
            ->where('kind', SimResourceKind::Room)->where('status', SimResourceStatus::Ready)
            ->where('quantity_total', 1)->where('is_exclusive', true)->whereNotNull('capacity')
            ->orderBy('id')->pluck('id')->all();
        $blocked = $resolver->unavailableResourceIds($ids, CarbonImmutable::parse($request->validated('starts_at')), CarbonImmutable::parse($request->validated('ends_at')));

        return response()->json(['rooms' => array_map(static fn ($id) => ['id' => $id, 'available' => ! in_array($id, $blocked, true)], $ids)]);
    }

    public function image(SimResource $simResource, RoomImageStorage $images): BinaryFileResponse
    {
        $this->authorize('viewImage', $simResource);
        abort_unless($images->validPath($simResource->image_path, $simResource->college_id), 404);
        $disk = Storage::disk('room-images');
        abort_unless($disk->exists($simResource->image_path), 404);

        return response()->file($disk->path($simResource->image_path), [
            'Content-Type' => 'image/jpeg', 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store', 'Content-Disposition' => 'inline; filename="room.jpg"',
        ]);
    }

    public function store(
        StoreSimResourceRequest $request,
        SaveResourceWithImage $action,
    ): RedirectResponse {
        $action->execute(
            data: $request->validated(),
            actor: $request->user(),
        );

        return back()->with('success', 'เพิ่มทรัพยากรเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, SimResource $simResource, DeleteSimResourceAction $action): RedirectResponse
    {
        $this->authorize('delete', $simResource);
        $action->execute($simResource, $request->user());

        return back()->with('success', 'ลบทรัพยากรเรียบร้อยแล้ว');
    }

    public function update(
        UpdateSimResourceRequest $request,
        SimResource $simResource,
        SaveResourceWithImage $action,
    ): RedirectResponse {
        $this->authorize('update', $simResource);

        $action->execute(
            resource: $simResource,
            data: $request->validated(),
            actor: $request->user(),
        );

        return back()->with('success', 'ปรับปรุงทรัพยากรเรียบร้อยแล้ว');
    }
}
