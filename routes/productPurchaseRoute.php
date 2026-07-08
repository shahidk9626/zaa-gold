<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductPurchasePreviewController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:purchase-preview.view')->group(function () {
        Route::get('/admin/purchase-preview', [ProductPurchasePreviewController::class, 'index'])->name('purchase-preview.index');
        Route::post('/admin/purchase-preview/calculate', [ProductPurchasePreviewController::class, 'calculate'])->name('purchase-preview.calculate');
        Route::post('/admin/purchase-preview/log-activity', [ProductPurchasePreviewController::class, 'logActivity'])->name('purchase-preview.log-activity');
    });
});
