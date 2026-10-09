<?php

use App\Core\Enums\AppPermission;
use App\Modules\Report\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/app/reports', ReportController::class)
    ->middleware(['auth', 'access-profile', 'permission:'.AppPermission::ReportView->value])->name('reports.index');
