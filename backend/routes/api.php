<?php

use App\Http\Controllers\Internal\ReminderController;
use App\Http\Controllers\Internal\UserController;
use App\Http\Controllers\Internal\TransactionController;
use App\Http\Controllers\Internal\SummaryController;
use Illuminate\Support\Facades\Route;

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
    
    // Reminder endpoints
    Route::get('/reminders/today-empty', [ReminderController::class, 'todayEmpty']);
    
    // Transaction endpoints (prepared for next step)
    Route::post('/transactions/batch', [TransactionController::class, 'batch']);
    Route::get('/summary/today', [SummaryController::class, 'today']);
});

