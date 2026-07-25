<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->isCustomer()) {
            return redirect()->route('customer.dashboard');
        }
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/generate-symlink', function () {
    // Target: storage/app/public/products
    $target = storage_path('app/public/products');
    
    // Shortcut: storage/products (inside the root storage folder)
    $shortcut = base_path('storage/products');
    
    if (file_exists($shortcut)) {
        @unlink($shortcut);
    }
    
    if (symlink($target, $shortcut)) {
        return 'Products storage symlink created successfully!';
    }
    
    return 'Failed to create symlink.';
});

require __DIR__.'/auth.php';
