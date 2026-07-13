<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:invoice.view')->group(function () {
        Route::get('/admin/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/admin/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    });

    Route::middleware('permission:invoice.download')->group(function () {
        Route::get('/admin/invoices/{id}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
    });

    Route::middleware('permission:invoice.print')->group(function () {
        Route::get('/admin/invoices/{id}/print', [InvoiceController::class, 'printInvoice'])->name('invoices.print');
    });

    Route::middleware('permission:invoice.export')->group(function () {
        Route::get('/admin/invoices/export/csv', [InvoiceController::class, 'exportCsv'])->name('invoices.export');
    });

    Route::middleware('permission:invoice.cancel')->group(function () {
        Route::post('/admin/invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    });
});
