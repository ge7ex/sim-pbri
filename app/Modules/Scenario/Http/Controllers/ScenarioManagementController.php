<?php

namespace App\Modules\Scenario\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Scenario\Actions\CreateCourseAction;
use App\Modules\Scenario\Actions\CreateScenarioAction;
use App\Modules\Scenario\Actions\UpdateCourseAction;
use App\Modules\Scenario\Actions\UpdateScenarioAction;
use App\Modules\Scenario\Http\Requests\StoreCourseRequest;
use App\Modules\Scenario\Http\Requests\StoreScenarioRequest;
use App\Modules\Scenario\Http\Requests\UpdateCourseRequest;
use App\Modules\Scenario\Http\Requests\UpdateScenarioRequest;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ScenarioManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = Course::query()
            ->where('college_id', $request->user()->college_id)
            ->with(['scenarios' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        return Inertia::render('Modules/Scenario/Pages/Index', [
            'courses' => $courses,
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
}
