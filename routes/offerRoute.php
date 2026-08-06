<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OfferController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:offers.view')->group(function () {
        Route::get('/admin/offers', [OfferController::class, 'index'])->name('offers.index');
        Route::get('/admin/offers/data', [OfferController::class, 'getData'])->name('offers.data');
        Route::get('/admin/offers/{id}', [OfferController::class, 'show'])->name('offers.show')->whereNumber('id');
    });

    Route::middleware('permission:offers.create')->group(function () {
        Route::get('/admin/offers/create', [OfferController::class, 'create'])->name('offers.create');
        Route::post('/admin/offers/store', [OfferController::class, 'store'])->name('offers.store');
    });

    Route::middleware('permission:offers.edit')->group(function () {
        Route::get('/admin/offers/{id}/edit', [OfferController::class, 'edit'])->name('offers.edit');
        Route::post('/admin/offers/update/{id}', [OfferController::class, 'update'])->name('offers.update');
    });

    Route::middleware('permission:offers.delete')->group(function () {
        Route::delete('/admin/offers/delete/{id}', [OfferController::class, 'destroy'])->name('offers.destroy');
    });

    Route::middleware('permission:offers.status')->group(function () {
        Route::post('/admin/offers/status/{id}', [OfferController::class, 'toggleStatus'])->name('offers.status');
    });
});
