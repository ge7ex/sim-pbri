<?php

use App\Core\Enums\AppPermission;
use App\Modules\Booking\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'access-profile'])
    ->prefix('app/bookings')
    ->name('bookings.')
    ->group(function (): void {
        Route::get('/create', [BookingController::class, 'create'])
            ->middleware('permission:'.AppPermission::BookingCreate->value)
            ->name('create');

        Route::post('/', [BookingController::class, 'store'])
            ->middleware('permission:'.AppPermission::BookingCreate->value)
            ->name('store');
    });
