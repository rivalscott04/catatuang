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

// Handle OPTIONS preflight for CORS - must be before other routes
Route::options('/admin/{any?}', function () {
    $origin = request()->header('Origin');
    $allowedOrigins = array_filter(
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173,https://catatuang.click,https://www.catatuang.click,https://catatuang.rivaldev.site')),
        function($o) {
            return !empty(trim($o));
        }
    );
    
    // Check if origin is allowed
    $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : null;
    
    $response = response('', 200);
    
    if ($allowedOrigin) {
        $response->header('Access-Control-Allow-Origin', $allowedOrigin);
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin');
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Max-Age', '3600');
    }
    
    return $response;
})->where('any', '.*');

// Admin API routes only (no views, frontend handles routing)
Route::prefix('admin')->group(function () {
    // Public admin API routes with enhanced rate limiting and anti-bot protection
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware(['login.rate.limit', 'throttle:10,1']);

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
        Route::put('/users/{id}/chat-style', [AdminDashboardController::class, 'updateChatStyle']);
        Route::delete('/users/{id}', [AdminDashboardController::class, 'deleteUser']);
        
        // Financial Data API
        Route::get('/financial-data', [AdminDashboardController::class, 'financialData']);
        Route::get('/users/{id}/expenses', [AdminDashboardController::class, 'getUserExpenses']);
        Route::get('/users/{id}/expenses/pdf', [AdminDashboardController::class, 'generateExpensePdf']);
        Route::get('/users/{id}/incomes', [AdminDashboardController::class, 'getUserIncomes']);
        Route::get('/users/{id}/incomes/pdf', [AdminDashboardController::class, 'generateIncomePdf']);
        
        // Pricing API
        Route::get('/pricing', [AdminPricingController::class, 'index']);
        Route::post('/pricing', [AdminPricingController::class, 'store']);
        Route::get('/pricing/{plan}', [AdminPricingController::class, 'show']);
        Route::put('/pricing/{id}', [AdminPricingController::class, 'update']);
        Route::delete('/pricing/{id}', [AdminPricingController::class, 'destroy']);
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
