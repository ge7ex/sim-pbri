<?php

use App\Core\Enums\AppPermission;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::middleware(['auth', 'access-profile'])
    ->prefix('app')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/resources', [WorkspaceController::class, 'resources'])
            ->middleware('permission:'.AppPermission::ResourceUpdate->value)
            ->name('resources.index');
    });

require base_path('app/Modules/Booking/Routes/web.php');
require __DIR__.'/auth.php';
