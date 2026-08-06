<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminCancellationController;

Route::middleware(['auth'])->group(function () {
    // View requests
    Route::middleware('permission:cancellations.view')->group(function () {
        Route::get('/admin/cancellations', [AdminCancellationController::class, 'index'])->name('admin.cancellations.index');
        Route::get('/admin/cancellations/{id}', [AdminCancellationController::class, 'show'])->name('admin.cancellations.show')->whereNumber('id');
        Route::post('/admin/cancellations/{id}/action', [AdminCancellationController::class, 'processAction'])->name('admin.cancellations.process_action')->whereNumber('id');
    });

    // Refund Processing
    Route::middleware('permission:cancellations.refund')->group(function () {
        Route::post('/admin/cancellations/{id}/refund/initiate', [AdminCancellationController::class, 'initiateRefund'])->name('admin.cancellations.refund_initiate')->whereNumber('id');
        Route::post('/admin/cancellations/{id}/refund/complete', [AdminCancellationController::class, 'completeRefund'])->name('admin.cancellations.refund_complete')->whereNumber('id');
    });
});
