<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('/admin/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/admin/inventory/transactions', [InventoryController::class, 'transactions'])->name('inventory.transactions');
    });

    Route::middleware('permission:inventory.create')->group(function () {
        Route::post('/admin/inventory/store', [InventoryController::class, 'store'])->name('inventory.store');
    });

    Route::middleware('permission:inventory.edit')->group(function () {
        Route::post('/admin/inventory/update/{id}', [InventoryController::class, 'update'])->name('inventory.update');
    });

    Route::middleware('permission:inventory.delete')->group(function () {
        Route::delete('/admin/inventory/delete/{id}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    });

    Route::middleware('permission:inventory.status')->post('/admin/inventory/status/{id}', [InventoryController::class, 'toggleStatus'])->name('inventory.status');

    Route::middleware('permission:inventory.adjust')->group(function () {
        Route::post('/admin/inventory/adjust/{id}', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    });
});
