<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KycController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:kyc.view')->group(function () {
        Route::get('/admin/kyc', [KycController::class, 'index'])->name('kyc.index');
        Route::get('/admin/kyc/{id}/view', [KycController::class, 'show'])->name('kyc.show');
    });

    Route::middleware('permission:kyc.create')->group(function () {
        Route::get('/admin/kyc/create', [KycController::class, 'create'])->name('kyc.create');
        Route::post('/admin/kyc/store', [KycController::class, 'store'])->name('kyc.store');
    });

    Route::middleware('permission:kyc.edit')->group(function () {
        Route::get('/admin/kyc/{id}/edit', [KycController::class, 'edit'])->name('kyc.edit');
        Route::post('/admin/kyc/update/{id}', [KycController::class, 'update'])->name('kyc.update');
    });

    Route::middleware('permission:kyc.delete')->group(function () {
        Route::delete('/admin/kyc/delete/{id}', [KycController::class, 'destroy'])->name('kyc.destroy');
    });

    Route::middleware('permission:kyc.approve')->post('/admin/kyc/{id}/approve', [KycController::class, 'approve'])->name('kyc.approve');
    Route::middleware('permission:kyc.reject')->post('/admin/kyc/{id}/reject', [KycController::class, 'reject'])->name('kyc.reject');
    Route::middleware('permission:kyc.download')->get('/admin/kyc/{id}/download', [KycController::class, 'download'])->name('kyc.download');
});
