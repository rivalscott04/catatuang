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
                    'current_plan' => $user->plan,
                ],
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
     * POST /api/upgrade/process
     * Process upgrade payment (placeholder for now)
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

        // TODO: Integrate with payment gateway here
        // For now, we'll simulate successful payment
        // In production, this should:
        // 1. Create payment record
        // 2. Redirect to payment gateway
        // 3. Handle callback from payment gateway

        // Simulate payment success (PLACEHOLDER)
        try {
            // Update user plan
            $oldPlan = $user->plan;
            $user->plan = $request->plan;
            $user->initializeSubscription($request->plan);
            $user->save();

            // Mark token as used
            $upgradeToken->markAsUsed();

            // Log upgrade
            Log::info('User upgraded plan', [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'old_plan' => $oldPlan,
                'new_plan' => $request->plan,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Upgrade berhasil!',
                'data' => [
                    'old_plan' => $oldPlan,
                    'new_plan' => $user->plan,
                    'redirect_url' => url("/upgrade/success?token={$request->token}"),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Upgrade failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'plan' => $request->plan,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses upgrade',
            ], 500);
        }
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

