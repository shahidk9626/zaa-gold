<?php

use App\Http\Controllers\AuditTrailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:audit.view')->group(function () {
        Route::get('/admin/audit-trail', [AuditTrailController::class, 'index'])->name('audit-trail.index');
    });

    Route::middleware('permission:audit.details')->group(function () {
        Route::get('/admin/audit-trail/{auditTrail}', [AuditTrailController::class, 'show'])->name('audit-trail.show');
    });

    Route::middleware('permission:audit.export')->group(function () {
        Route::get('/admin/audit-trail/export/{type}', [AuditTrailController::class, 'export'])
            ->whereIn('type', ['csv', 'excel', 'pdf'])
            ->name('audit-trail.export');
    });
});
