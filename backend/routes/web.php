<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPricingController;
use Illuminate\Support\Facades\Route;

// CSRF token endpoint for SPA
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});

// Admin API routes only (no views, frontend handles routing)
Route::prefix('admin')->group(function () {
    // Public admin API routes with rate limiting
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');

    // Protected admin API routes
    Route::middleware(['admin.auth'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::put('/password', [AdminAuthController::class, 'updatePassword']);
        Route::put('/username', [AdminAuthController::class, 'updateUsername']);
        
        // Dashboard API
        Route::get('/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/users', [AdminDashboardController::class, 'users']);
        Route::get('/users/{id}', [AdminDashboardController::class, 'user']);
        Route::put('/users/{id}/plan', [AdminDashboardController::class, 'updateUserPlan']);
        Route::delete('/users/{id}', [AdminDashboardController::class, 'deleteUser']);
        
        // Financial Data API
        Route::get('/financial-data', [AdminDashboardController::class, 'financialData']);
        
        // Pricing API
        Route::get('/pricing', [AdminPricingController::class, 'index']);
        Route::get('/pricing/{plan}', [AdminPricingController::class, 'show']);
        Route::put('/pricing/{id}', [AdminPricingController::class, 'update']);
    });
});

// SPA catch-all: return index.html for all unmatched GET routes (Svelte handles routing)
// Must be last - Laravel will match more specific routes first
// Only catch routes that don't start with API prefixes
Route::fallback(function () {
    $indexPath = base_path('../index.html');
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath), 200)
            ->header('Content-Type', 'text/html');
    }
    return response('SPA index.html not found', 404);
});
