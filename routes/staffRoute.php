<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaffController;

Route::middleware(['auth'])->group(function () {
    // User Permissions management
    Route::middleware('permission:user-permissions.view')->get('/user-permissions', [StaffController::class, 'userPermissionsIndex'])->name('user-permissions.index');
    Route::middleware('permission:user-permissions.edit')->get('/user-permissions/{id}', [StaffController::class, 'manageUserPermissions'])->name('user-permissions.manage');
    Route::middleware('permission:user-permissions.edit')->post('/user-permissions/{id}', [StaffController::class, 'saveUserPermissions'])->name('user-permissions.save');

    // Staff Management CRUD
    Route::middleware('permission:staff.view')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/{id}/view', [StaffController::class, 'show'])->name('staff.show');
    });
    Route::middleware('permission:staff.create')->group(function () {
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff/store', [StaffController::class, 'store'])->name('staff.store');
    });
    Route::middleware('permission:staff.edit')->group(function () {
        Route::get('/staff/edit/{slug}', [StaffController::class, 'edit'])->name('staff.edit');
        Route::post('/staff/update/{slug}', [StaffController::class, 'update'])->name('staff.update');
    });
    Route::middleware('permission:staff.delete')->group(function () {
        Route::delete('/staff/delete/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');
        Route::post('/staff/bulk-delete', [StaffController::class, 'bulkDestroy'])->name('staff.bulk-destroy');
        Route::delete('/staff/delete-document/{id}', [StaffController::class, 'deleteDocument'])->name('staff.delete-document');
    });
    Route::middleware('permission:staff.status')->post('/staff/status/{id}', [StaffController::class, 'toggleStatus'])->name('staff.status');
});
