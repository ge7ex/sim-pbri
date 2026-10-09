<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::middleware(['auth', 'access-profile'])
    ->prefix('app')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
    });

require base_path('app/Modules/Booking/Routes/web.php');
require base_path('app/Modules/SimResource/Routes/web.php');
require base_path('app/Modules/Scenario/Routes/web.php');
require base_path('app/Modules/Simulator/Routes/web.php');
require base_path('app/Modules/Report/Routes/web.php');
require __DIR__.'/auth.php';
