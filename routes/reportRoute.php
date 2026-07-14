<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:report.view')->group(function () {
        Route::get('/admin/reports', [ReportController::class, 'index'])->name('reports.dashboard');
    });

    Route::middleware('permission:report.export')->group(function () {
        Route::get('/admin/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');
    });
});
