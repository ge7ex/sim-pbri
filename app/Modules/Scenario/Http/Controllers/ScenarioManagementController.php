<?php

namespace App\Modules\Scenario\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Scenario\Actions\CreateCourseAction;
use App\Modules\Scenario\Actions\CreateScenarioAction;
use App\Modules\Scenario\Actions\SyncScenarioEquipmentTemplateAction;
use App\Modules\Scenario\Actions\UpdateCourseAction;
use App\Modules\Scenario\Actions\UpdateScenarioAction;
use App\Modules\Scenario\Http\Requests\StoreCourseRequest;
use App\Modules\Scenario\Http\Requests\StoreScenarioRequest;
use App\Modules\Scenario\Http\Requests\UpdateCourseRequest;
use App\Modules\Scenario\Http\Requests\UpdateScenarioEquipmentTemplateRequest;
use App\Modules\Scenario\Http\Requests\UpdateScenarioRequest;
use App\Modules\Scenario\Http\Requests\UpdateScenarioSimulatorTypesRequest;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class ScenarioManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $collegeId = $request->user()->college_id;

        $courses = Course::query()
            ->where('college_id', $collegeId)
            ->with([
                'scenarios' => fn ($query) => $query
                    ->with([
                        'recommendedResources' => fn ($resourceQuery) => $resourceQuery
                            ->where('college_id', $collegeId)->where('kind', SimResourceKind::Equipment)->orderBy('name'),
                        'recommendedSimulatorTypes' => fn ($typeQuery) => $typeQuery->where('college_id', $collegeId)->select('simulator_types.id', 'name', 'is_active'),
                    ])
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        $equipment = SimResource::query()
            ->where('college_id', $collegeId)
            ->where('kind', SimResourceKind::Equipment)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'status',
                'quantity_total',
                'location',
            ]);

        return Inertia::render('Modules/Scenario/Pages/Index', [
            'courses' => $courses,
            'equipment' => $equipment,
            'simulatorTypes' => SimulatorType::where('college_id', $collegeId)->orderBy('name')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function storeCourse(
        StoreCourseRequest $request,
        CreateCourseAction $action,
    ): RedirectResponse {
        $action->execute($request->validated(), $request->user());

        return back()->with('success', 'เพิ่มรายวิชาเรียบร้อยแล้ว');
    }

    public function updateCourse(
        UpdateCourseRequest $request,
        Course $course,
        UpdateCourseAction $action,
    ): RedirectResponse {
        $this->authorize('update', $course);

        $action->execute($course, $request->validated());

        return back()->with('success', 'ปรับปรุงรายวิชาเรียบร้อยแล้ว');
    }

    public function storeScenario(
        StoreScenarioRequest $request,
        CreateScenarioAction $action,
    ): RedirectResponse {
        $course = Course::query()->findOrFail($request->integer('course_id'));
        $this->authorize('update', $course);

        $action->execute($course, $request->validated());

        return back()->with('success', 'เพิ่มสถานการณ์จำลองเรียบร้อยแล้ว');
    }

    public function updateScenario(
        UpdateScenarioRequest $request,
        Scenario $scenario,
        UpdateScenarioAction $action,
    ): RedirectResponse {
        $this->authorize('update', $scenario);

        $preferredCourse = Course::query()->findOrFail($request->integer('course_id'));
        $this->authorize('update', $preferredCourse);

        $action->execute($scenario, $preferredCourse, $request->validated());

        return back()->with('success', 'ปรับปรุงสถานการณ์จำลองเรียบร้อยแล้ว');
    }

    public function updateSimulatorTypes(UpdateScenarioSimulatorTypesRequest $request, Scenario $scenario): RedirectResponse
    {
        DB::transaction(function () use ($request, $scenario): void {
            $locked = Scenario::whereKey($scenario->id)->lockForUpdate()->firstOrFail();
            $this->authorize('update', $locked);
            $locked->recommendedSimulatorTypes()->sync($request->validated('simulator_type_ids'));
        }, attempts: 3);

        return back()->with('success', 'ปรับปรุงประเภทหุ่นจำลองแนะนำเรียบร้อยแล้ว');
    }

    public function updateEquipmentTemplate(
        UpdateScenarioEquipmentTemplateRequest $request,
        Scenario $scenario,
        SyncScenarioEquipmentTemplateAction $action,
    ): RedirectResponse {
        $this->authorize('update', $scenario);

        /** @var list<array{id: int, quantity: int}> $equipment */
        $equipment = $request->validated('equipment');

        $action->execute($scenario, $equipment);

        return back()->with('success', 'ปรับปรุงอุปกรณ์แนะนำเรียบร้อยแล้ว');
    }
}
