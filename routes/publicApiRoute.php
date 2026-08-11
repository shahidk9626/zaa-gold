<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicApiController;

Route::get('/gold-price', [PublicApiController::class, 'getGoldPrice']);
Route::get('/products', [PublicApiController::class, 'getProducts']);
Route::post('/website-enquiries', [PublicApiController::class, 'storeWebsiteEnquiry'])->middleware('throttle:6,1');
Route::get('/system-status', [PublicApiController::class, 'getSystemStatus']);
