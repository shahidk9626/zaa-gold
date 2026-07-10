<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmiCalculatorController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:emi-calculator.view')->group(function () {
        Route::get('/admin/emi-calculator', [EmiCalculatorController::class, 'index'])->name('emi-calculator.index');
        Route::post('/admin/emi-calculator/calculate', [EmiCalculatorController::class, 'calculate'])->name('emi-calculator.calculate');
        Route::post('/admin/emi-calculator/outstanding', [EmiCalculatorController::class, 'getOutstandingDetails'])->name('emi-calculator.outstanding');
        Route::post('/admin/emi-calculator/log-activity', [EmiCalculatorController::class, 'logActivity'])->name('emi-calculator.log-activity');
        
        // Export PDF inside calculator
        Route::get('/admin/emi-calculator/outstanding/pdf', [EmiCalculatorController::class, 'exportOutstandingPdf'])->name('emi-calculator.outstanding.pdf');
    });
});
