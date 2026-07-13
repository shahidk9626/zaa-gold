<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:payment.view')->group(function () {
        Route::get('/admin/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/admin/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
    });

    Route::middleware('permission:payment.collect')->group(function () {
        Route::get('/admin/bookings/{booking_id}/payments/collect/{schedule_id}', [PaymentController::class, 'collectForm'])->name('payments.collect_form');
        Route::post('/admin/bookings/{booking_id}/payments/collect/{schedule_id}', [PaymentController::class, 'collectStore'])->name('payments.collect_store');
    });

    Route::middleware('permission:payment.export')->group(function () {
        Route::get('/admin/payments/export/csv', [PaymentController::class, 'exportCsv'])->name('payments.export');
    });

    Route::middleware('permission:payment.edit')->group(function () {
        Route::get('/admin/payments/{id}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
        Route::put('/admin/payments/{id}', [PaymentController::class, 'update'])->name('payments.update');
    });

    Route::middleware('permission:payment.delete')->group(function () {
        Route::delete('/admin/payments/{id}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    });
});
