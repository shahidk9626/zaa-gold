<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoldPriceController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:gold-price.view')->group(function () {
        Route::get('/admin/gold-prices', [GoldPriceController::class, 'index'])->name('gold-prices.index');
    });

    Route::middleware('permission:gold-price.create')->group(function () {
        Route::post('/admin/gold-prices/store', [GoldPriceController::class, 'store'])->name('gold-prices.store');
    });

    Route::middleware('permission:gold-price.edit')->group(function () {
        Route::post('/admin/gold-prices/update/{id}', [GoldPriceController::class, 'update'])->name('gold-prices.update');
    });

    Route::middleware('permission:gold-price.delete')->group(function () {
        Route::delete('/admin/gold-prices/delete/{id}', [GoldPriceController::class, 'destroy'])->name('gold-prices.destroy');
    });

    Route::middleware('permission:gold-price.status')->post('/admin/gold-prices/status/{id}', [GoldPriceController::class, 'toggleStatus'])->name('gold-prices.status');

    Route::middleware('permission:gold-price.history')->get('/admin/gold-prices/history', [GoldPriceController::class, 'history'])->name('gold-prices.history');
});
