<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmiPlanController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:emi-plan.view')->group(function () {
        Route::get('/admin/emi-plans', [EmiPlanController::class, 'index'])->name('emi-plans.index');
        Route::get('/admin/emi-plans/{id}/view', [EmiPlanController::class, 'show'])->name('emi-plans.show');
    });

    Route::middleware('permission:emi-plan.create')->group(function () {
        Route::get('/admin/emi-plans/create', [EmiPlanController::class, 'create'])->name('emi-plans.create');
        Route::post('/admin/emi-plans/store', [EmiPlanController::class, 'store'])->name('emi-plans.store');
    });

    Route::middleware('permission:emi-plan.edit')->group(function () {
        Route::get('/admin/emi-plans/{id}/edit', [EmiPlanController::class, 'edit'])->name('emi-plans.edit');
        Route::post('/admin/emi-plans/update/{id}', [EmiPlanController::class, 'update'])->name('emi-plans.update');
    });

    Route::middleware('permission:emi-plan.delete')->group(function () {
        Route::delete('/admin/emi-plans/delete/{id}', [EmiPlanController::class, 'destroy'])->name('emi-plans.destroy');
    });

    Route::middleware('permission:emi-plan.status')->post('/admin/emi-plans/status/{id}', [EmiPlanController::class, 'toggleStatus'])->name('emi-plans.status');
});
