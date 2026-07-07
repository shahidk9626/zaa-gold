<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard.dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard.dashboard');
});
