<?php

use App\Core\Enums\AppPermission;
use App\Modules\Scenario\Http\Controllers\ScenarioManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'access-profile'])
    ->prefix('app/scenarios')
    ->name('scenarios.')
    ->group(function (): void {
        Route::get('/', [ScenarioManagementController::class, 'index'])
            ->middleware('permission:'.AppPermission::ScenarioView->value)
            ->name('index');

        Route::post('/courses', [ScenarioManagementController::class, 'storeCourse'])
            ->middleware('permission:'.AppPermission::ScenarioCreate->value)
            ->name('courses.store');

        Route::put('/courses/{course}', [ScenarioManagementController::class, 'updateCourse'])
            ->middleware('permission:'.AppPermission::ScenarioUpdate->value)
            ->name('courses.update');

        Route::post('/', [ScenarioManagementController::class, 'storeScenario'])
            ->middleware('permission:'.AppPermission::ScenarioCreate->value)
            ->name('store');

        Route::put('/{scenario}/equipment-template', [ScenarioManagementController::class, 'updateEquipmentTemplate'])
            ->middleware('permission:'.AppPermission::ScenarioUpdate->value)
            ->name('equipment-template.update');

        Route::put('/{scenario}', [ScenarioManagementController::class, 'updateScenario'])
            ->middleware('permission:'.AppPermission::ScenarioUpdate->value)
            ->name('update');
    });
