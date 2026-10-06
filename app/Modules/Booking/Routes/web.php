<?php

use App\Core\Enums\AppPermission;
use App\Modules\Booking\Http\Controllers\BookingCancellationController;
use App\Modules\Booking\Http\Controllers\BookingController;
use App\Modules\Booking\Http\Controllers\BookingReviewController;
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

        Route::post('/{booking}/cancel', BookingCancellationController::class)
            ->middleware('permission:'.AppPermission::BookingCancel->value)
            ->name('cancel');

        Route::post('/{booking}/approve', [BookingReviewController::class, 'approve'])
            ->middleware('permission:'.AppPermission::BookingApprove->value)
            ->name('approve');

        Route::post('/{booking}/reject', [BookingReviewController::class, 'reject'])
            ->middleware('permission:'.AppPermission::BookingApprove->value)
            ->name('reject');

        Route::post('/{booking}/recall', [BookingReviewController::class, 'recall'])
            ->middleware('permission:'.AppPermission::BookingApprove->value)
            ->name('recall');
    });
