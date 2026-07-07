<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Roles CRUD Routes
    Route::middleware('permission:roles.view')->get('/roles', [App\Http\Controllers\RoleController::class, 'index'])->name('roles.index');
    Route::middleware('permission:roles.create')->post('/roles/store', [App\Http\Controllers\RoleController::class, 'store'])->name('roles.store');
    Route::middleware('permission:roles.edit')->post('/roles/update/{id}', [App\Http\Controllers\RoleController::class, 'update'])->name('roles.update');
    Route::middleware('permission:roles.delete')->delete('/roles/delete/{id}', [App\Http\Controllers\RoleController::class, 'destroy'])->name('roles.destroy');
    Route::middleware('permission:roles.delete')->post('/roles/bulk-delete', [App\Http\Controllers\RoleController::class, 'bulkDestroy'])->name('roles.bulk-destroy');
    Route::middleware('permission:roles.status')->post('/roles/status/{id}', [App\Http\Controllers\RoleController::class, 'toggleStatus'])->name('roles.status');
    Route::middleware('permission:roles.edit')->get('/roles/permissions/{id}', [App\Http\Controllers\RoleController::class, 'getPermissions'])->name('roles.permissions');

    // Role Permissions management
    Route::middleware('permission:roles.view')->get('/role-permissions', [App\Http\Controllers\RoleController::class, 'rolePermissionsIndex'])->name('role-permissions.index');
    Route::middleware('permission:roles.edit')->get('/role-permissions/{id}', [App\Http\Controllers\RoleController::class, 'manageRolePermissions'])->name('role-permissions.manage');

    // User Permissions management
    Route::middleware('permission:user-permissions.view')->get('/user-permissions', [App\Http\Controllers\StaffController::class, 'userPermissionsIndex'])->name('user-permissions.index');
    Route::middleware('permission:user-permissions.edit')->get('/user-permissions/{id}', [App\Http\Controllers\StaffController::class, 'manageUserPermissions'])->name('user-permissions.manage');
    Route::middleware('permission:user-permissions.edit')->post('/user-permissions/{id}', [App\Http\Controllers\StaffController::class, 'saveUserPermissions'])->name('user-permissions.save');

    // Staff Management CRUD
    Route::middleware('permission:staff.view')->group(function () {
        Route::get('/staff', [App\Http\Controllers\StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/{id}/view', [App\Http\Controllers\StaffController::class, 'show'])->name('staff.show');
    });
    Route::middleware('permission:staff.create')->group(function () {
        Route::get('/staff/create', [App\Http\Controllers\StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff/store', [App\Http\Controllers\StaffController::class, 'store'])->name('staff.store');
    });
    Route::middleware('permission:staff.edit')->group(function () {
        Route::get('/staff/edit/{slug}', [App\Http\Controllers\StaffController::class, 'edit'])->name('staff.edit');
        Route::post('/staff/update/{slug}', [App\Http\Controllers\StaffController::class, 'update'])->name('staff.update');
    });
    Route::middleware('permission:staff.delete')->group(function () {
        Route::delete('/staff/delete/{id}', [App\Http\Controllers\StaffController::class, 'destroy'])->name('staff.destroy');
        Route::post('/staff/bulk-delete', [App\Http\Controllers\StaffController::class, 'bulkDestroy'])->name('staff.bulk-destroy');
        Route::delete('/staff/delete-document/{id}', [App\Http\Controllers\StaffController::class, 'deleteDocument'])->name('staff.delete-document');
    });
    Route::middleware('permission:staff.status')->post('/staff/status/{id}', [App\Http\Controllers\StaffController::class, 'toggleStatus'])->name('staff.status');
});

require __DIR__.'/auth.php';
