<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeliveryController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:delivery.view')->group(function () {
        Route::get('/admin/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('/admin/deliveries/{id}', [DeliveryController::class, 'show'])->name('deliveries.show');
    });

    Route::middleware('permission:delivery.request')->group(function () {
        Route::post('/admin/bookings/{bookingId}/deliveries/request', [DeliveryController::class, 'storeRequest'])->name('deliveries.store_request');
    });

    Route::middleware('permission:delivery.approve')->group(function () {
        Route::post('/admin/deliveries/{id}/approve', [DeliveryController::class, 'approve'])->name('deliveries.approve');
        Route::post('/admin/deliveries/{id}/regenerate-otp', [DeliveryController::class, 'regenerateOtp'])->name('deliveries.regenerate_otp');
    });

    Route::middleware('permission:delivery.dispatch')->group(function () {
        Route::post('/admin/deliveries/{id}/dispatch', [DeliveryController::class, 'dispatchDelivery'])->name('deliveries.dispatch');
    });

    Route::middleware('permission:delivery.complete')->group(function () {
        Route::post('/admin/deliveries/{id}/complete', [DeliveryController::class, 'complete'])->name('deliveries.complete');
    });

    Route::middleware('permission:delivery.cancel')->group(function () {
        Route::post('/admin/deliveries/{id}/cancel', [DeliveryController::class, 'cancel'])->name('deliveries.cancel');
    });

    Route::middleware('permission:delivery.download')->group(function () {
        Route::get('/admin/deliveries/{id}/download', [DeliveryController::class, 'downloadChallan'])->name('deliveries.download');
    });

    Route::middleware('permission:delivery.export')->group(function () {
        Route::get('/admin/deliveries/export/csv', [DeliveryController::class, 'exportCsv'])->name('deliveries.export');
    });
});
