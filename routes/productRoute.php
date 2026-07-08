<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:product.view')->group(function () {
        Route::get('/admin/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/admin/products/{id}/view', [ProductController::class, 'show'])->name('products.show');
    });

    Route::middleware('permission:product.create')->group(function () {
        Route::get('/admin/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/admin/products/store', [ProductController::class, 'store'])->name('products.store');
    });

    Route::middleware('permission:product.edit')->group(function () {
        Route::get('/admin/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::post('/admin/products/update/{id}', [ProductController::class, 'update'])->name('products.update');
    });

    Route::middleware('permission:product.delete')->group(function () {
        Route::delete('/admin/products/delete/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/admin/products/bulk-delete', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
    });

    Route::middleware('permission:product.status')->post('/admin/products/status/{id}', [ProductController::class, 'toggleStatus'])->name('products.status');
});
