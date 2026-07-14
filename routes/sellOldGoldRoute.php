<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SellOldGoldController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:sell-old-gold.view')->group(function () {
        Route::get('/admin/sell-old-gold', [SellOldGoldController::class, 'index'])->name('sell-old-gold.index');
        Route::get('/admin/sell-old-gold/{id}', [SellOldGoldController::class, 'show'])->name('sell-old-gold.show');
    });

    Route::middleware('permission:sell-old-gold.edit')->group(function () {
        Route::get('/admin/sell-old-gold/create/enquiry', [SellOldGoldController::class, 'create'])->name('sell-old-gold.create');
        Route::post('/admin/sell-old-gold/store', [SellOldGoldController::class, 'store'])->name('sell-old-gold.store');
        Route::get('/admin/sell-old-gold/{id}/edit', [SellOldGoldController::class, 'edit'])->name('sell-old-gold.edit');
        Route::post('/admin/sell-old-gold/{id}/update', [SellOldGoldController::class, 'update'])->name('sell-old-gold.update');
        Route::post('/admin/sell-old-gold/{id}/status', [SellOldGoldController::class, 'changeStatus'])->name('sell-old-gold.change_status');
        Route::post('/admin/sell-old-gold/{id}/assign', [SellOldGoldController::class, 'assignStaff'])->name('sell-old-gold.assign');
        Route::post('/admin/sell-old-gold/{id}/note', [SellOldGoldController::class, 'addNote'])->name('sell-old-gold.add_note');
        Route::delete('/admin/sell-old-gold/{id}/delete', [SellOldGoldController::class, 'destroy'])->name('sell-old-gold.destroy');
    });

    Route::middleware('permission:sell-old-gold.export')->group(function () {
        Route::get('/admin/sell-old-gold/export/csv', [SellOldGoldController::class, 'exportCsv'])->name('sell-old-gold.export');
    });
});
