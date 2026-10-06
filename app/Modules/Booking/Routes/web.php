<?php

use App\Core\Enums\AppPermission;
use App\Modules\Booking\Http\Controllers\BookingCalendarController;
use App\Modules\Booking\Http\Controllers\BookingCancellationController;
use App\Modules\Booking\Http\Controllers\BookingController;
use App\Modules\Booking\Http\Controllers\BookingReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'access-profile'])
    ->prefix('app')
    ->group(function (): void {
        Route::get('/bookings', [BookingController::class, 'index'])
            ->middleware('permission:'.AppPermission::BookingView->value)
            ->name('bookings.index');

        Route::get('/bookings/create', [BookingController::class, 'create'])
            ->middleware('permission:'.AppPermission::BookingCreate->value)
            ->name('bookings.create');

        Route::post('/bookings', [BookingController::class, 'store'])
            ->middleware('permission:'.AppPermission::BookingCreate->value)
            ->name('bookings.store');

        Route::get('/bookings/{booking}', [BookingController::class, 'show'])
            ->middleware('permission:'.AppPermission::BookingView->value)
            ->name('bookings.show');

        Route::post('/bookings/{booking}/cancel', BookingCancellationController::class)
            ->middleware('permission:'.AppPermission::BookingCancel->value)
            ->name('bookings.cancel');

        Route::get('/calendar', BookingCalendarController::class)
            ->middleware('permission:'.AppPermission::BookingView->value)
            ->name('calendar.index');

        Route::get('/review', [BookingReviewController::class, 'index'])
            ->middleware('permission:'.AppPermission::BookingApprove->value)
            ->name('review.index');

        Route::post('/bookings/{booking}/approve', [BookingReviewController::class, 'approve'])
            ->middleware('permission:'.AppPermission::BookingApprove->value)
            ->name('bookings.approve');

        Route::post('/bookings/{booking}/reject', [BookingReviewController::class, 'reject'])
            ->middleware('permission:'.AppPermission::BookingApprove->value)
            ->name('bookings.reject');

        Route::post('/bookings/{booking}/recall', [BookingReviewController::class, 'recall'])
            ->middleware('permission:'.AppPermission::BookingApprove->value)
            ->name('bookings.recall');
    });
