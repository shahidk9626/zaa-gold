<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\PlanController;
use App\Http\Controllers\Customer\MyPlanController;
use App\Http\Controllers\Customer\EmiController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Customer\BookingPaymentController;
use App\Http\Controllers\Customer\CertificateController;
use App\Http\Controllers\Customer\DeliveryController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\Customer\SupportController;
use App\Http\Controllers\Customer\OutstandingController;

Route::middleware(['auth', 'customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Buy Gold Plans (Marketplace & Booking flow)
    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/live-price', [PlanController::class, 'livePrice'])->name('plans.live_price');
    Route::get('/plans/calculate/{productId}/{planId}', [PlanController::class, 'calculatePriceSheet'])->name('plans.calculate');
    Route::post('/plans/book', [PlanController::class, 'book'])->name('plans.book');
    Route::get('/plans/{id}', [PlanController::class, 'show'])->name('plans.show');

    // Customer Purchased Plans (My Plans)
    Route::get('/my-plans', [MyPlanController::class, 'index'])->name('my-plans.index');
    Route::get('/my-plans/{id}', [MyPlanController::class, 'show'])->name('my-plans.show');

    Route::get('/emi/history', [EmiController::class, 'history'])->name('emi.history');
    Route::get('/emi/repay', [EmiController::class, 'repay'])->name('emi.repay');
    Route::get('/emi/{scheduleId}/pay', [EmiController::class, 'payForm'])->name('emi.pay_form');
    Route::post('/emi/{scheduleId}/pay', [EmiController::class, 'processPay'])->name('emi.process_pay');

    Route::get('/outstanding', [OutstandingController::class, 'index'])->name('outstanding.index');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{id}/receipt', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt');
    Route::get('/booking-payments/{transaction}/checkout', [BookingPaymentController::class, 'checkout'])->name('booking-payments.checkout');
    Route::get('/booking-payments/{transaction}/callback', [BookingPaymentController::class, 'callback'])->name('booking-payments.callback');

    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{bookingId}/price-lock', [CertificateController::class, 'downloadPriceLock'])->name('certificates.price_lock');
    Route::get('/certificates/invoice/{id}', [CertificateController::class, 'downloadInvoice'])->name('certificates.invoice');

    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/{id}', [DeliveryController::class, 'show'])->name('deliveries.show');
    Route::post('/deliveries/{bookingId}/request', [DeliveryController::class, 'storeRequest'])->name('deliveries.store_request');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/kyc', [ProfileController::class, 'submitKyc'])->name('profile.submit_kyc');

    Route::post('/dashboard/dismiss-reminder', [DashboardController::class, 'dismissReminder'])->name('dashboard.dismiss_reminder');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
});

Route::post('/payment/cashfree/webhook', \App\Http\Controllers\CashfreeWebhookController::class)
    ->name('payments.cashfree.webhook');

Route::get('/pay/{token}', [\App\Http\Controllers\GatewayPaymentController::class, 'pay'])
    ->name('payments.links.pay');
Route::get('/payment/gateway/{transaction}/callback', [\App\Http\Controllers\GatewayPaymentController::class, 'callback'])
    ->name('payments.gateway.callback');

// OTP routes for unauthenticated / guest customers
use App\Http\Controllers\Customer\OtpController;

Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/verify-email', [OtpController::class, 'verifyEmailView'])->name('verify-email-view');
    Route::post('/verify-email', [OtpController::class, 'verifyEmail'])->name('verify-email');
    Route::post('/resend-email-otp', [OtpController::class, 'resendEmailOtp'])->name('resend-email-otp');

    Route::get('/forgot-password/verify', [OtpController::class, 'verifyForgotPasswordView'])->name('verify-forgot-password-view');
    Route::post('/forgot-password/verify', [OtpController::class, 'verifyForgotPassword'])->name('verify-forgot-password');
    Route::post('/forgot-password/resend-otp', [OtpController::class, 'resendForgotPasswordOtp'])->name('resend-forgot-password-otp');

    Route::get('/reset-password', [OtpController::class, 'resetPasswordView'])->name('reset-password-view');
    Route::post('/reset-password', [OtpController::class, 'resetPassword'])->name('reset-password');
});
