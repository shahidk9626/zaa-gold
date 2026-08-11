<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemSettingController;

Route::get('/maintenance', function () {
    return response()->view('maintenance', [], 503);
})->name('maintenance');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/system-settings', [SystemSettingController::class, 'index'])->name('admin.system-settings.index');
    Route::post('/admin/system-settings/toggle', [SystemSettingController::class, 'toggle'])->name('admin.system-settings.toggle');
});
