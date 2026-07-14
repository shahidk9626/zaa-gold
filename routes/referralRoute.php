<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReferralController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:referral.view')->group(function () {
        Route::get('/admin/referrals', [ReferralController::class, 'index'])->name('referrals.index');
        Route::get('/admin/referrals/create', [ReferralController::class, 'create'])->name('referrals.create');
        Route::post('/admin/referrals/store', [ReferralController::class, 'store'])->name('referrals.store');
        Route::get('/admin/referrals/{id}', [ReferralController::class, 'show'])->name('referrals.show');
    });

    Route::middleware('permission:referral.edit')->group(function () {
        Route::get('/admin/referrals/{id}/edit', [ReferralController::class, 'edit'])->name('referrals.edit');
        Route::post('/admin/referrals/{id}/update', [ReferralController::class, 'update'])->name('referrals.update');
    });

    Route::middleware('permission:referral.export')->group(function () {
        Route::get('/admin/referrals/export/csv', [ReferralController::class, 'exportCsv'])->name('referrals.export');
    });
});
