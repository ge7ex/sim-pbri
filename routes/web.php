<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/app', DashboardController::class)
    ->middleware(['auth', 'access-profile'])
    ->name('dashboard');

require __DIR__.'/auth.php';
