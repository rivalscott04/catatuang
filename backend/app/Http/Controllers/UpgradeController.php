<?php

namespace App\Http\Controllers;

use App\Models\UpgradeToken;
use App\Models\User;
use App\Models\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UpgradeController extends Controller
{
    /**
     * GET /api/upgrade/validate/{token}
     * Validate upgrade token and return user info
     */
    public function validateToken(string $token)
    {
        $upgradeToken = UpgradeToken::findValid($token);

        if (!$upgradeToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah expired',
            ], 404);
        }

        $user = User::where('phone_number', $upgradeToken->phone_number)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'phone_number' => $user->phone_number,
                    'name' => $user->name,
                ],
                'current_plan' => $user->plan,
            ],
        ]);
    }

    /**
     * GET /api/upgrade/{token}
     * Get available plans for upgrade (exclude current plan and free plan)
     */
    public function getPlans(string $token)
    {
        $upgradeToken = UpgradeToken::findValid($token);

        if (!$upgradeToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah expired',
            ], 404);
        }

        $user = User::where('phone_number', $upgradeToken->phone_number)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        // Get available plans (exclude free, unlimited, and current plan)
        $availablePlans = Pricing::where('is_active', true)
            ->where('plan', '!=', 'free')
            ->where('plan', '!=', 'unlimited')
            ->where('plan', '!=', $user->plan)
            ->orderBy('display_order', 'asc')
            ->orderBy('plan', 'asc')
            ->get()
            ->map(function ($pricing) {
                $features = $pricing->features;
                if (is_string($features)) {
                    $features = json_decode($features, true) ?? [];
                }
                if (!is_array($features)) {
                    $features = [];
                }

                return [
                    'id' => $pricing->id,
                    'plan' => $pricing->plan,
                    'price' => (int) $pricing->price,
                    'description' => $pricing->description ?? '',
                    'features' => $features,
                    'badge_text' => $pricing->badge_text,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'current_plan' => $user->plan,
                'available_plans' => $availablePlans->all(),
            ],
        ]);
    }

    /**
     * POST /api/upgrade/checkout
     * Generate Pakasir payment URL for checkout
     */
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:64',
            'plan' => 'required|string|in:pro,vip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $upgradeToken = UpgradeToken::findValid($request->token);

        if (!$upgradeToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah expired',
            ], 404);
        }

        $user = User::where('phone_number', $upgradeToken->phone_number)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        // Explicitly reject unlimited plan
        if ($request->plan === 'unlimited') {
            return response()->json([
                'success' => false,
                'message' => 'Paket unlimited tidak tersedia untuk upgrade',
            ], 400);
        }

        // Check if plan is available
        $pricing = Pricing::where('plan', $request->plan)
            ->where('is_active', true)
            ->first();

        if (!$pricing) {
            return response()->json([
                'success' => false,
                'message' => 'Paket tidak tersedia',
            ], 404);
        }

        // Check if user is already on this plan or higher
        $planHierarchy = ['free' => 0, 'pro' => 1, 'vip' => 2];
        $currentPlanLevel = $planHierarchy[$user->plan] ?? 0;
        $newPlanLevel = $planHierarchy[$request->plan] ?? 0;

        if ($currentPlanLevel >= $newPlanLevel) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah menggunakan paket yang sama atau lebih tinggi',
            ], 400);
        }

        try {
            // Generate order_id using token (Pakasir will use this in webhook)
            $orderId = $request->token; // Use token as order_id for webhook matching
            
            // Get Pakasir configuration
            $pakasirSlug = env('PAKASIR_PROJECT_SLUG', 'catatuang');
            $amount = (int) $pricing->price;
            
            // Build Pakasir payment URL with QRIS only
            $paymentUrl = "https://app.pakasir.com/pay/{$pakasirSlug}/{$amount}?order_id={$orderId}&qris_only=1";
            
            // Calculate fee (if any) - Pakasir typically charges around 0.5-1% fee
            // For now, we'll assume no additional fee on top of pricing
            $fee = 0;
            $totalPayment = $amount + $fee;
            
            // Calculate expiry time (15 minutes from now)
            $expiredAt = now()->addMinutes(15);

            Log::info('Checkout initialized', [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'plan' => $request->plan,
                'amount' => $amount,
                'order_id' => $orderId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil dibuat',
                'data' => [
                    'plan' => $request->plan,
                    'amount' => $amount,
                    'fee' => $fee,
                    'total_payment' => $totalPayment,
                    'payment_url' => $paymentUrl,
                    'order_id' => $orderId,
                    'expired_at' => $expiredAt->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Checkout failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'plan' => $request->plan,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat checkout',
            ], 500);
        }
    }

    /**
     * GET /api/upgrade/payment-status
     * Check payment status for upgrade
     */
    public function paymentStatus(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan',
            ], 400);
        }

        $upgradeToken = UpgradeToken::where('token', $token)->first();

        if (!$upgradeToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid',
            ], 404);
        }

        // Check if token has been used (payment completed)
        if ($upgradeToken->used_at) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                    'used_at' => $upgradeToken->used_at->toIso8601String(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'pending',
            ],
        ]);
    }

    /**
     * POST /api/upgrade/process
     * Process upgrade payment (placeholder for now)
     * This is kept for backward compatibility but now redirects to checkout
     */
    public function processUpgrade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:64',
            'plan' => 'required|string|in:pro,vip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $upgradeToken = UpgradeToken::findValid($request->token);

        if (!$upgradeToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah expired',
            ], 404);
        }

        $user = User::where('phone_number', $upgradeToken->phone_number)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        // Explicitly reject unlimited plan
        if ($request->plan === 'unlimited') {
            return response()->json([
                'success' => false,
                'message' => 'Paket unlimited tidak tersedia untuk upgrade',
            ], 400);
        }

        // Check if plan is available
        $pricing = Pricing::where('plan', $request->plan)
            ->where('is_active', true)
            ->first();

        if (!$pricing) {
            return response()->json([
                'success' => false,
                'message' => 'Paket tidak tersedia',
            ], 404);
        }

        // Check if user is already on this plan or higher
        $planHierarchy = ['free' => 0, 'pro' => 1, 'vip' => 2];
        $currentPlanLevel = $planHierarchy[$user->plan] ?? 0;
        $newPlanLevel = $planHierarchy[$request->plan] ?? 0;

        if ($currentPlanLevel >= $newPlanLevel) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah menggunakan paket yang sama atau lebih tinggi',
            ], 400);
        }

        // Return success - frontend will handle redirect to checkout
        return response()->json([
            'success' => true,
            'message' => 'Redirect ke checkout',
            'data' => [
                'token' => $request->token,
                'plan' => $request->plan,
            ],
        ]);
    }

    /**
     * GET /api/upgrade/success
     * Get upgrade success info
     */
    public function getSuccessInfo(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan',
            ], 400);
        }

        $upgradeToken = UpgradeToken::where('token', $token)
            ->whereNotNull('used_at')
            ->first();

        if (!$upgradeToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid',
            ], 404);
        }

        $user = User::where('phone_number', $upgradeToken->phone_number)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'phone_number' => $user->phone_number,
                ],
                'plan' => $user->plan,
                'upgraded_at' => $upgradeToken->used_at->toIso8601String(),
            ],
        ]);
    }
}

