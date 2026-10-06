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

        Route::get('/bookings', [WorkspaceController::class, 'bookingHistory'])
            ->middleware('permission:'.AppPermission::BookingView->value)
            ->name('bookings.index');

        Route::get('/bookings/create', [WorkspaceController::class, 'bookingCreate'])
            ->middleware('permission:'.AppPermission::BookingCreate->value)
            ->name('bookings.create');

        Route::get('/calendar', [WorkspaceController::class, 'calendar'])
            ->middleware('permission:'.AppPermission::BookingView->value)
            ->name('calendar.index');

        Route::get('/review', [WorkspaceController::class, 'review'])
            ->middleware('permission:'.AppPermission::BookingApprove->value)
            ->name('review.index');

        Route::get('/resources', [WorkspaceController::class, 'resources'])
            ->middleware('permission:'.AppPermission::ResourceUpdate->value)
            ->name('resources.index');
    });

require __DIR__.'/auth.php';
