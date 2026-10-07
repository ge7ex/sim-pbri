<?php

namespace App\Modules\Simulator\Http\Controllers;

use App\Core\Enums\AppPermission;
use App\Http\Controllers\Controller;
use App\Modules\Booking\Services\BookingAvailabilityResolver;
use App\Modules\Simulator\Actions\ManageSimulatorAction;
use App\Modules\Simulator\Enums\SimulatorAssetStatus;
use App\Modules\Simulator\Http\Requests\SaveSimulatorAssetRequest;
use App\Modules\Simulator\Http\Requests\SaveSimulatorTypeRequest;
use App\Modules\Simulator\Http\Requests\SimulatorAvailabilityRequest;
use App\Modules\Simulator\Http\Requests\StoreMaintenanceRequest;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorType;
use App\Modules\Simulator\Services\StraightLineDepreciation;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SimulatorController extends Controller
{
    public function index(Request $request, StraightLineDepreciation $depreciation): Response
    {
        $actor = $request->user();
        $financial = $actor->canAccess(AppPermission::SimulatorUpdate);
        $columns = ['id', 'simulator_type_id', 'asset_name', 'asset_code', 'status', 'location', 'description'];
        $historyColumns = ['id', 'simulator_asset_id', 'maintenance_date', 'description', 'performed_by'];
        if ($financial) {
            $columns = [...$columns, 'purchase_year', 'purchase_price', 'useful_life_years'];
            $historyColumns = [...$historyColumns, 'cost', 'note', 'created_by_user_id'];
        }

        $assets = SimulatorAsset::where('college_id', $actor->college_id)
            ->with([
                'simulatorType' => fn ($q) => $q->where('college_id', $actor->college_id)->select('id', 'name', 'is_active'),
                'maintenanceRecords' => fn ($q) => $q->select($historyColumns)->orderByDesc('maintenance_date')->orderByDesc('id'),
            ])
            ->orderBy('asset_name')->paginate(20, $columns);
        if ($financial) {
            $assets->through(function (SimulatorAsset $asset) use ($depreciation) {
                $asset->setAttribute('depreciation', $depreciation->calculate($asset, now()->year));

                return $asset;
            });
        }

        return Inertia::render('Modules/Simulator/Pages/Index', [
            'types' => SimulatorType::where('college_id', $actor->college_id)->orderBy('name')->get(['id', 'name', 'description', 'is_active']),
            'assets' => $assets,
        ]);
    }

    public function availability(SimulatorAvailabilityRequest $request, BookingAvailabilityResolver $resolver): JsonResponse
    {
        $collegeId = $request->user()->college_id;
        $ids = SimulatorAsset::where('college_id', $collegeId)->where('status', SimulatorAssetStatus::Active)
            ->whereHas('simulatorType', fn ($q) => $q->where('college_id', $collegeId)->where('is_active', true))
            ->orderBy('id')->pluck('id')->all();
        $blocked = $resolver->unavailableSimulatorIds($ids, CarbonImmutable::parse($request->validated('starts_at')), CarbonImmutable::parse($request->validated('ends_at')));

        return response()->json(['assets' => array_map(static fn ($id) => ['id' => $id, 'available' => ! in_array($id, $blocked, true)], $ids)]);
    }

    public function storeType(SaveSimulatorTypeRequest $request, ManageSimulatorAction $action): RedirectResponse
    {
        $action->saveType($request->validated(), $request->user());

        return back()->with('success', 'เพิ่มประเภทหุ่นจำลองเรียบร้อยแล้ว');
    }

    public function updateType(SaveSimulatorTypeRequest $request, SimulatorType $simulatorType, ManageSimulatorAction $action): RedirectResponse
    {
        $action->saveType($request->validated(), $request->user(), $simulatorType);

        return back()->with('success', 'ปรับปรุงประเภทหุ่นจำลองเรียบร้อยแล้ว');
    }

    public function storeAsset(SaveSimulatorAssetRequest $request, ManageSimulatorAction $action): RedirectResponse
    {
        $action->saveAsset($request->validated(), $request->user());

        return back()->with('success', 'เพิ่มทรัพย์สินเรียบร้อยแล้ว');
    }

    public function updateAsset(SaveSimulatorAssetRequest $request, SimulatorAsset $simulatorAsset, ManageSimulatorAction $action): RedirectResponse
    {
        $action->saveAsset($request->validated(), $request->user(), $simulatorAsset);

        return back()->with('success', 'ปรับปรุงทรัพย์สินเรียบร้อยแล้ว');
    }

    public function storeMaintenance(StoreMaintenanceRequest $request, SimulatorAsset $simulatorAsset, ManageSimulatorAction $action): RedirectResponse
    {
        $action->recordMaintenance($simulatorAsset, $request->validated(), $request->user());

        return back()->with('success', 'บันทึกประวัติบำรุงรักษาเรียบร้อยแล้ว');
    }
}
