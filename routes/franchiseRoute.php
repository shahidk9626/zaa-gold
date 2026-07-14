<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FranchiseController;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:franchise.view')->group(function () {
        Route::get('/admin/franchise', [FranchiseController::class, 'index'])->name('franchise.index');
        Route::get('/admin/franchise/{id}', [FranchiseController::class, 'show'])->name('franchise.show');
    });

    Route::middleware('permission:franchise.edit')->group(function () {
        Route::get('/admin/franchise/create/new', [FranchiseController::class, 'create'])->name('franchise.create');
        Route::post('/admin/franchise/store', [FranchiseController::class, 'store'])->name('franchise.store');
        Route::get('/admin/franchise/{id}/edit', [FranchiseController::class, 'edit'])->name('franchise.edit');
        Route::post('/admin/franchise/{id}/update', [FranchiseController::class, 'update'])->name('franchise.update');
        Route::post('/admin/franchise/{id}/status', [FranchiseController::class, 'changeStatus'])->name('franchise.change_status');
        Route::post('/admin/franchise/{id}/assign', [FranchiseController::class, 'assignStaff'])->name('franchise.assign');
        Route::post('/admin/franchise/{id}/note', [FranchiseController::class, 'addNote'])->name('franchise.add_note');
        Route::delete('/admin/franchise/{id}/delete', [FranchiseController::class, 'destroy'])->name('franchise.destroy');
    });

    Route::middleware('permission:franchise.export')->group(function () {
        Route::get('/admin/franchise/export/csv', [FranchiseController::class, 'exportCsv'])->name('franchise.export');
    });
});
