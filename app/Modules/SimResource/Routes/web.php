<?php

use App\Core\Enums\AppPermission;
use App\Modules\SimResource\Http\Controllers\SimResourceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'access-profile'])
    ->prefix('app/resources')
    ->name('resources.')
    ->group(function (): void {
        Route::get('/', [SimResourceController::class, 'index'])
            ->middleware('permission:'.AppPermission::ResourceView->value)
            ->name('index');

        Route::post('/', [SimResourceController::class, 'store'])
            ->middleware('permission:'.AppPermission::ResourceCreate->value)
            ->name('store');

        Route::put('/{simResource}', [SimResourceController::class, 'update'])
            ->middleware('permission:'.AppPermission::ResourceUpdate->value)
            ->name('update');
    });
