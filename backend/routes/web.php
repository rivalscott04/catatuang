<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// CSRF token endpoint for SPA
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});

// Registration routes
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/register/success', [AuthController::class, 'showSuccess'])->name('register.success');
