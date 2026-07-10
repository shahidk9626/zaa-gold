<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductPurchasePreviewController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:purchase-preview.view')->group(function () {
        Route::get('/admin/purchase-preview', [ProductPurchasePreviewController::class, 'index'])->name('purchase-preview.index');
        Route::post('/admin/purchase-preview/calculate', [ProductPurchasePreviewController::class, 'calculate'])->name('purchase-preview.calculate');
        Route::post('/admin/purchase-preview/log-activity', [ProductPurchasePreviewController::class, 'logActivity'])->name('purchase-preview.log-activity');
        
        // Outstanding statement schedule calculation (AJAX)
        Route::post('/admin/purchase-preview/outstanding', [ProductPurchasePreviewController::class, 'getOutstandingDetails'])->name('purchase-preview.outstanding');
    });

    // PDF export route (permission based)
    Route::middleware('permission:emi-outstanding.export')->group(function () {
        Route::get('/admin/purchase-preview/outstanding/pdf', [ProductPurchasePreviewController::class, 'exportOutstandingPdf'])->name('purchase-preview.outstanding.pdf');
    });
});
