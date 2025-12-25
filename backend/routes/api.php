<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Internal\ReminderController;
use App\Http\Controllers\Internal\UserController;
use App\Http\Controllers\Internal\TransactionController;
use App\Http\Controllers\Internal\SummaryController;
use App\Http\Controllers\Internal\SubscriptionController;
use App\Http\Controllers\Internal\ReportController;
use App\Http\Controllers\Internal\UploadController;
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
    
    // Report endpoints
    Route::post('/reports/pdf-expense-monthly', [ReportController::class, 'generateExpenseMonthlyPdf']);
    
    // Upload endpoints
    Route::post('/uploads/create', [UploadController::class, 'create']);
    Route::post('/uploads/confirm', [UploadController::class, 'confirm']);
    Route::get('/uploads/pending', [UploadController::class, 'getPending']);
});

