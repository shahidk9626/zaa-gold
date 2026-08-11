<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebsiteEnquiryController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:website-enquiries.view')->group(function () {
        Route::get('/admin/website-enquiries', [WebsiteEnquiryController::class, 'index'])->name('website-enquiries.index');
        Route::get('/admin/website-enquiries/{id}', [WebsiteEnquiryController::class, 'show'])->name('website-enquiries.show');
    });

    Route::middleware('permission:website-enquiries.update')->group(function () {
        Route::post('/admin/website-enquiries/{id}/status', [WebsiteEnquiryController::class, 'updateStatus'])->name('website-enquiries.update_status');
    });

    Route::middleware('permission:website-enquiries.delete')->group(function () {
        Route::delete('/admin/website-enquiries/{id}/delete', [WebsiteEnquiryController::class, 'destroy'])->name('website-enquiries.destroy');
    });
});
