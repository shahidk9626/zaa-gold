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
    $publicFolders = ['products', 'staff_docs', 'customer_docs', 'kyc', 'certificates', 'invoices', 'qrcodes'];
    $results = [];

    foreach ($publicFolders as $folder) {
        $target = storage_path('app/public/' . $folder);
        $shortcut = base_path('storage/' . $folder);

        // Ensure the source target folder exists before linking
        if (!file_exists($target)) {
            @mkdir($target, 0755, true);
        }

        if (file_exists($shortcut)) {
            @unlink($shortcut);
        }

        if (@symlink($target, $shortcut)) {
            $results[] = "Folder '{$folder}' linked successfully.";
        } else {
            $results[] = "Failed to link folder '{$folder}'.";
        }
    }

    return implode('<br>', $results);
});

require __DIR__.'/auth.php';
