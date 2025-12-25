<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReminderController extends Controller
{
    /**
     * Get users who haven't created transaction today and reminder_enabled=true
     * 
     * GET /internal/reminders/today-empty
     */
    public function todayEmpty(): JsonResponse
    {
        // Get current date in app timezone
        $today = now(config('app.timezone'))->format('Y-m-d');

        // Query users who:
        // - status = active
        // - reminder_enabled = true
        // - no transaction today
        $users = User::where('status', 'active')
            ->where('reminder_enabled', true)
            ->whereDoesntHave('transactions', function ($query) use ($today) {
                $query->whereDate('tanggal', $today);
            })
            ->select('phone_number')
            ->get();

        return response()->json([
            'success' => true,
            'date' => $today,
            'items' => $users->map(function ($user) {
                return [
                    'phone_number' => $user->phone_number,
                ];
            }),
        ]);
    }
}


