<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmiScheduleController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:emi-schedule.view')->group(function () {
        Route::get('/admin/emi-schedules', [EmiScheduleController::class, 'index'])->name('emi-schedules.index');
    });

    Route::middleware('permission:emi-schedule.export')->group(function () {
        Route::get('/admin/emi-schedules/export/csv', [EmiScheduleController::class, 'exportCsv'])->name('emi-schedules.export');
    });
});
