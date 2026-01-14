<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Internal\ReminderController;
use App\Http\Controllers\Internal\UserController;
use App\Http\Controllers\Internal\TransactionController;
use App\Http\Controllers\Internal\SummaryController;
use App\Http\Controllers\Internal\SubscriptionController;
use App\Http\Controllers\Internal\ReportController;
use App\Http\Controllers\Internal\UploadController;
use App\Http\Controllers\Internal\UpgradeController as InternalUpgradeController;
use App\Http\Controllers\Internal\BudgetController;
use App\Http\Controllers\UpgradeController;
use App\Http\Controllers\PakasirWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

// Public registration endpoint (no authentication required)
// Allow OPTIONS for CORS preflight
Route::options('/register', function () {
    return response('', 200);
});
Route::post('/register', [AuthController::class, 'register']);
Route::get('/bot-number', [AuthController::class, 'getBotNumber']);

// Public pricing endpoint
Route::get('/pricing', [\App\Http\Controllers\PricingController::class, 'index']);

// Public upgrade endpoints
Route::get('/upgrade/validate/{token}', [UpgradeController::class, 'validateToken']);
Route::get('/upgrade/{token}', [UpgradeController::class, 'getPlans']);
Route::post('/upgrade/process', [UpgradeController::class, 'processUpgrade']);
Route::post('/upgrade/checkout', [UpgradeController::class, 'checkout']);
Route::get('/upgrade/payment-status', [UpgradeController::class, 'paymentStatus']);
Route::get('/upgrade/success', [UpgradeController::class, 'getSuccessInfo']);

// Public webhook endpoints (no authentication required)
Route::get('/webhook/pakasir', [PakasirWebhookController::class, 'healthCheck']);
Route::post('/webhook/pakasir', [PakasirWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| Internal API Routes (for n8n)
|--------------------------------------------------------------------------
|
| All routes here require X-API-KEY header authentication
|
*/

Route::middleware(['api.key'])->prefix('internal')->group(function () {
    // User endpoints
    Route::post('/users/check-or-create', [UserController::class, 'checkOrCreate']);
    Route::post('/users/reminder', [UserController::class, 'updateReminder']);
    Route::post('/users/style', [UserController::class, 'updateStyle']);
    Route::post('/users/increment-chat', [UserController::class, 'incrementChat']);
    Route::get('/users/limits', [UserController::class, 'getLimits']);
    
    // Reminder endpoints
    Route::get('/reminders/today-empty', [ReminderController::class, 'todayEmpty']);
    
    // Subscription endpoints
    Route::get('/subscriptions/expiring-soon', [SubscriptionController::class, 'expiringSoon']);
    Route::get('/subscriptions/expired', [SubscriptionController::class, 'expired']);
    Route::post('/subscriptions/mark-expired', [SubscriptionController::class, 'markExpired']);
    
    // Transaction endpoints (prepared for next step)
    Route::post('/transactions/batch', [TransactionController::class, 'batch']);
    Route::get('/summary/today', [SummaryController::class, 'today']);
    Route::get('/summary/today-detail', [SummaryController::class, 'todayDetail']);
    Route::get('/summary/month-balance', [SummaryController::class, 'monthBalance']);
    Route::get('/summary/statistics-by-category', [SummaryController::class, 'statisticsByCategory']);
    Route::get('/summary/by-category', [SummaryController::class, 'byCategory']);
    
    // Report endpoints
    Route::post('/reports/pdf-expense-monthly', [ReportController::class, 'generateExpenseMonthlyPdf']);
    
    // Upload endpoints
    Route::post('/uploads/create', [UploadController::class, 'create']);
    Route::post('/uploads/download-image', [UploadController::class, 'downloadImage']);
    Route::post('/uploads/confirm', [UploadController::class, 'confirm']);
    Route::get('/uploads/pending', [UploadController::class, 'getPending']);
    
    // Budget endpoints
    Route::post('/budget/set', [BudgetController::class, 'set']);
    Route::get('/budget/get', [BudgetController::class, 'get']);
    
    // Upgrade endpoints (for n8n)
    Route::get('/upgrade/generate-link', [InternalUpgradeController::class, 'generateLink']);
    Route::post('/upgrade/generate-link', [InternalUpgradeController::class, 'generateLink']);
});

