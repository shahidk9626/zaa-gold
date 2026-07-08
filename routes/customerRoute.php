<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:customer.view')->group(function () {
        Route::get('/admin/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/admin/customers/{id}/view', [CustomerController::class, 'show'])->name('customers.show');
    });

    Route::middleware('permission:customer.create')->group(function () {
        Route::get('/admin/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/admin/customers/store', [CustomerController::class, 'store'])->name('customers.store');
    });

    Route::middleware('permission:customer.edit')->group(function () {
        Route::get('/admin/customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::post('/admin/customers/update/{id}', [CustomerController::class, 'update'])->name('customers.update');
    });

    Route::middleware('permission:customer.delete')->group(function () {
        Route::delete('/admin/customers/delete/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('/admin/customers/bulk-delete', [CustomerController::class, 'bulkDestroy'])->name('customers.bulk-destroy');
        Route::delete('/admin/customers/delete-document/{id}', [CustomerController::class, 'deleteDocument'])->name('customers.delete-document');
    });

    Route::middleware('permission:customer.status')->group(function () {
        Route::post('/admin/customers/status/{id}', [CustomerController::class, 'toggleStatus'])->name('customers.status');
        Route::post('/admin/customers/{id}/verify', [CustomerController::class, 'verify'])->name('customers.verify');
    });

    Route::middleware('permission:customer.export')->get('/admin/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::middleware('permission:customer.import')->group(function () {
        Route::post('/admin/customers/import', [CustomerController::class, 'import'])->name('customers.import');
        Route::get('/admin/customers/import-template', [CustomerController::class, 'downloadTemplate'])->name('customers.import-template');
    });
});
