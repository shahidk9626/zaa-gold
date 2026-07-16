<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoldBookingController;

Route::middleware(['auth'])->group(function () {
    // Booking list dashboard
    Route::middleware('permission:booking.view')->group(function () {
        Route::get('/admin/bookings', [GoldBookingController::class, 'index'])->name('bookings.index');
    });

    // Create booking endpoint
    Route::middleware('permission:booking.create')->group(function () {
        Route::post('/admin/bookings/store', [GoldBookingController::class, 'store'])->name('bookings.store');
        Route::get('/admin/booking-payments/{transaction}/checkout', [GoldBookingController::class, 'checkout'])->name('admin.booking-payments.checkout');
        Route::get('/admin/booking-payments/{transaction}/callback', [GoldBookingController::class, 'callback'])->name('admin.booking-payments.callback');
    });

    // Booking details panel
    Route::middleware('permission:booking.view_details')->group(function () {
        Route::get('/admin/bookings/{id}', [GoldBookingController::class, 'show'])->name('bookings.show');
    });

    // Certificate PDF downloads
    Route::middleware('permission:booking.download_certificate')->group(function () {
        Route::get('/admin/bookings/{id}/certificate', [GoldBookingController::class, 'downloadCertificate'])->name('bookings.download_certificate');
    });

    // Status change transitions
    Route::middleware('permission:booking.change_status')->group(function () {
        Route::post('/admin/bookings/{id}/status', [GoldBookingController::class, 'changeStatus'])->name('bookings.change_status');
    });

    // Export bookings list
    Route::middleware('permission:booking.export')->group(function () {
        Route::get('/admin/bookings/export/csv', [GoldBookingController::class, 'exportCsv'])->name('bookings.export');
    });
});
