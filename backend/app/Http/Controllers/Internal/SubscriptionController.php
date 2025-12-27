<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * GET /internal/subscriptions/expiring-soon
     * Get list of users whose subscription expires in X days
     */
    public function expiringSoon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $days = $request->input('days', 2);
        $targetDate = now()->addDays($days)->toDateString();

        $users = User::where('subscription_status', 'active')
            ->whereDate('subscription_expires_at', $targetDate)
            ->whereNotNull('subscription_expires_at')
            ->select('phone_number', 'plan', 'subscription_expires_at', 'response_style')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'days' => $days,
                'target_date' => $targetDate,
                'count' => $users->count(),
                'items' => $users->map(function ($user) use ($days) {
                    return [
                        'phone_number' => $user->phone_number,
                        'plan' => $user->plan,
                        'subscription_expires_at' => $user->subscription_expires_at,
                        'days_until_expiry' => $days,
                        'response_style' => $user->response_style,
                    ];
                }),
            ],
        ]);
    }

    /**
     * GET /internal/subscriptions/expired
     * Get list of users whose subscription has expired
     */
    public function expired(Request $request): JsonResponse
    {
        $users = User::where('subscription_status', 'active')
            ->whereDate('subscription_expires_at', '<', now()->toDateString())
            ->whereNotNull('subscription_expires_at')
            ->select('phone_number', 'plan', 'subscription_expires_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $users->count(),
                'items' => $users->map(function ($user) {
                    return [
                        'phone_number' => $user->phone_number,
                        'plan' => $user->plan,
                        'subscription_expires_at' => $user->subscription_expires_at,
                    ];
                }),
            ],
        ]);
    }

    /**
     * POST /internal/subscriptions/mark-expired
     * Mark subscriptions as expired (called by cron or manual)
     */
    public function markExpired(Request $request): JsonResponse
    {
        $updated = DB::table('users')
            ->where('subscription_status', 'active')
            ->whereDate('subscription_expires_at', '<', now()->toDateString())
            ->whereNotNull('subscription_expires_at')
            ->update([
                'subscription_status' => 'expired',
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'updated_count' => $updated,
                'message' => "Marked {$updated} subscriptions as expired",
            ],
        ]);
    }
}





