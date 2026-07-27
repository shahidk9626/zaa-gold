<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CashCollectionController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:cash-collection.view')->group(function () {
        Route::get('/admin/cash-collections', [CashCollectionController::class, 'index'])->name('admin.cash-collections.index');
        Route::get('/admin/cash-collections/{id}', [CashCollectionController::class, 'show'])->name('admin.cash-collections.show');
    });

    Route::middleware('permission:cash-collection.verify')->group(function () {
        Route::post('/admin/cash-collections/{id}/verify', [CashCollectionController::class, 'verify'])->name('admin.cash-collections.verify');
    });

    Route::middleware('permission:cash-collection.reject')->group(function () {
        Route::post('/admin/cash-collections/{id}/reject', [CashCollectionController::class, 'reject'])->name('admin.cash-collections.reject');
    });
});
