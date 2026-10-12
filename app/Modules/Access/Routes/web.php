<?php

use App\Core\Enums\AppPermission;
use App\Modules\Access\Http\Controllers\AccountPasswordController;
use App\Modules\Access\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'access-profile'])
    ->prefix('app')
    ->group(function (): void {
        Route::get('/account/password', [AccountPasswordController::class, 'edit'])
            ->name('account.password.edit');
        Route::put('/account/password', [AccountPasswordController::class, 'update'])
            ->name('account.password.update');

        Route::middleware('permission:'.AppPermission::UserManage->value)
            ->prefix('users')
            ->name('users.')
            ->group(function (): void {
                Route::get('/', [UserManagementController::class, 'index'])->name('index');
                Route::post('/', [UserManagementController::class, 'store'])->name('store');
                Route::put('/{user}', [UserManagementController::class, 'update'])->whereNumber('user')->name('update');
                Route::post('/{user}/password', [UserManagementController::class, 'resetPassword'])->whereNumber('user')->name('password.reset');
            });
    });
