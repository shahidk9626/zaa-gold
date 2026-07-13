<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReceiptController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:receipt.view')->group(function () {
        Route::get('/admin/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    });

    Route::middleware('permission:receipt.download')->group(function () {
        Route::get('/admin/receipts/{payment_id}/download', [ReceiptController::class, 'downloadReceiptPdf'])->name('receipts.download');
    });
});
