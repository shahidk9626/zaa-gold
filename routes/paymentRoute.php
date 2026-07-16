<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentLinkController;
use App\Http\Controllers\PaymentLogController;
use App\Http\Controllers\PaymentDashboardController;
use App\Http\Controllers\PaymentReconciliationController;
use App\Http\Controllers\FailedPaymentController;
use App\Http\Controllers\PaymentExportController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:payment.dashboard')->get('/admin/payments/dashboard', [PaymentDashboardController::class, 'index'])->name('payments.dashboard');

    Route::middleware('permission:payment.view')->group(function () {
        Route::get('/admin/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/admin/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
    });

    Route::middleware('permission:payment.links')->group(function () {
        Route::get('/admin/payment-links', [PaymentLinkController::class, 'index'])->name('payment-links.index');
        Route::get('/admin/payment-links/{paymentLink}', [PaymentLinkController::class, 'show'])->name('payment-links.show');
    });

    Route::middleware('permission:payment.logs')->group(function () {
        Route::get('/admin/payment-logs', [PaymentLogController::class, 'index'])->name('payment-logs.index');
        Route::get('/admin/payment-logs/{paymentLog}', [PaymentLogController::class, 'show'])->name('payment-logs.show');
    });

    Route::middleware('permission:payment.failed')->group(function () {
        Route::get('/admin/failed-payments', [FailedPaymentController::class, 'index'])->name('payments.failed');
    });

    Route::middleware('permission:payment.reconciliation')->group(function () {
        Route::get('/admin/payment-reconciliation', [PaymentReconciliationController::class, 'index'])->name('payments.reconciliation');
        Route::post('/admin/payment-reconciliation/{transaction}/refresh', [PaymentReconciliationController::class, 'refresh'])->name('payments.reconciliation.refresh');
        Route::post('/admin/payment-reconciliation/{transaction}/verify', [PaymentReconciliationController::class, 'verify'])->name('payments.reconciliation.verify');
    });

    Route::middleware('permission:payment.export')->group(function () {
        Route::get('/admin/payment-management/{module}/export/{type}', [PaymentExportController::class, 'export'])
            ->whereIn('module', ['logs', 'links', 'failed', 'reconciliation'])
            ->whereIn('type', ['csv', 'excel', 'pdf'])
            ->name('payments.management.export');
    });

    Route::middleware('permission:payment.collect')->group(function () {
        Route::get('/admin/bookings/{booking_id}/payments/collect/{schedule_id}', [PaymentController::class, 'collectForm'])->name('payments.collect_form');
        Route::post('/admin/bookings/{booking_id}/payments/collect/{schedule_id}', [PaymentController::class, 'collectStore'])->name('payments.collect_store');
        Route::post('/admin/bookings/{booking_id}/payments/link/{schedule_id}', [PaymentLinkController::class, 'generate'])->name('payment-links.generate');
        Route::post('/admin/payment-links/{paymentLink}/regenerate', [PaymentLinkController::class, 'regenerate'])->name('payment-links.regenerate');
        Route::post('/admin/payment-links/{paymentLink}/copy', [PaymentLinkController::class, 'copy'])->name('payment-links.copy');
    });

    Route::middleware('permission:payment.retry')->group(function () {
        Route::post('/admin/failed-payments/{transaction}/retry', [FailedPaymentController::class, 'retry'])->name('payments.failed.retry');
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
