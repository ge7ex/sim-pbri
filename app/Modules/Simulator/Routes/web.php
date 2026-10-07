<?php

use App\Core\Enums\AppPermission;
use App\Modules\Simulator\Http\Controllers\SimulatorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'access-profile'])->prefix('app/simulators')->name('simulators.')->group(function (): void {
    Route::get('/availability', [SimulatorController::class, 'availability'])->middleware('permission:'.AppPermission::BookingCreate->value)->name('availability');
    Route::get('/', [SimulatorController::class, 'index'])->middleware('permission:'.AppPermission::SimulatorView->value)->name('index');
    Route::post('/types', [SimulatorController::class, 'storeType'])->middleware('permission:'.AppPermission::SimulatorCreate->value)->name('types.store');
    Route::put('/types/{simulatorType}', [SimulatorController::class, 'updateType'])->middleware('permission:'.AppPermission::SimulatorUpdate->value)->name('types.update');
    Route::post('/assets', [SimulatorController::class, 'storeAsset'])->middleware('permission:'.AppPermission::SimulatorCreate->value)->name('assets.store');
    Route::put('/assets/{simulatorAsset}', [SimulatorController::class, 'updateAsset'])->middleware('permission:'.AppPermission::SimulatorUpdate->value)->name('assets.update');
    Route::post('/assets/{simulatorAsset}/maintenance', [SimulatorController::class, 'storeMaintenance'])->middleware('permission:'.AppPermission::SimulatorMaintenance->value)->name('maintenance.store');
});
