<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\PlanController;
use App\Http\Controllers\Customer\EmiController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Customer\CertificateController;
use App\Http\Controllers\Customer\DeliveryController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\Customer\SupportController;
use App\Http\Controllers\Customer\OutstandingController;

Route::middleware(['auth', 'customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/{id}', [PlanController::class, 'show'])->name('plans.show');

    Route::get('/emi/history', [EmiController::class, 'history'])->name('emi.history');
    Route::get('/emi/repay', [EmiController::class, 'repay'])->name('emi.repay');
    Route::get('/emi/{scheduleId}/pay', [EmiController::class, 'payForm'])->name('emi.pay_form');
    Route::post('/emi/{scheduleId}/pay', [EmiController::class, 'processPay'])->name('emi.process_pay');

    Route::get('/outstanding', [OutstandingController::class, 'index'])->name('outstanding.index');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{id}/receipt', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt');

    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{bookingId}/price-lock', [CertificateController::class, 'downloadPriceLock'])->name('certificates.price_lock');
    Route::get('/certificates/invoice/{id}', [CertificateController::class, 'downloadInvoice'])->name('certificates.invoice');

    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/{id}', [DeliveryController::class, 'show'])->name('deliveries.show');
    Route::post('/deliveries/{bookingId}/request', [DeliveryController::class, 'storeRequest'])->name('deliveries.store_request');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
});
