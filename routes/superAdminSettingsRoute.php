<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdminSettingsController;

Route::middleware(['auth', 'super-admin'])->group(function () {
    Route::get('/admin/super-admin-settings', [SuperAdminSettingsController::class, 'index'])->name('admin.super-admin-settings.index');

    Route::middleware('throttle:6,1')->group(function () {
        Route::put('/admin/super-admin-settings/email', [SuperAdminSettingsController::class, 'updateEmail'])->name('admin.super-admin-settings.email');
        Route::put('/admin/super-admin-settings/password', [SuperAdminSettingsController::class, 'updatePassword'])->name('admin.super-admin-settings.password');
    });
});
