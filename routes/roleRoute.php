<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;

Route::middleware(['auth'])->group(function () {
    // Roles CRUD Routes
    Route::middleware('permission:roles.view')->get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::middleware('permission:roles.create')->post('/roles/store', [RoleController::class, 'store'])->name('roles.store');
    Route::middleware('permission:roles.edit')->post('/roles/update/{id}', [RoleController::class, 'update'])->name('roles.update');
    Route::middleware('permission:roles.delete')->delete('/roles/delete/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::middleware('permission:roles.delete')->post('/roles/bulk-delete', [RoleController::class, 'bulkDestroy'])->name('roles.bulk-destroy');
    Route::middleware('permission:roles.status')->post('/roles/status/{id}', [RoleController::class, 'toggleStatus'])->name('roles.status');
    Route::middleware('permission:roles.edit')->get('/roles/permissions/{id}', [RoleController::class, 'getPermissions'])->name('roles.permissions');

    // Role Permissions management
    Route::middleware('permission:roles.view')->get('/role-permissions', [RoleController::class, 'rolePermissionsIndex'])->name('role-permissions.index');
    Route::middleware('permission:roles.edit')->get('/role-permissions/{id}', [RoleController::class, 'manageRolePermissions'])->name('role-permissions.manage');
});
