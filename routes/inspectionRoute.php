<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoldInspectionEnquiryController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:inspection.view')->group(function () {
        Route::get('/admin/inspection', [GoldInspectionEnquiryController::class, 'index'])->name('inspections.index');
        Route::get('/admin/inspection/{id}', [GoldInspectionEnquiryController::class, 'show'])->name('inspections.show');
    });

    Route::middleware('permission:inspection.edit')->group(function () {
        Route::post('/admin/inspection/{id}/status', [GoldInspectionEnquiryController::class, 'updateStatus'])->name('inspections.update_status');
    });

    Route::middleware('permission:inspection.delete')->group(function () {
        Route::delete('/admin/inspection/{id}/delete', [GoldInspectionEnquiryController::class, 'destroy'])->name('inspections.destroy');
    });
});
