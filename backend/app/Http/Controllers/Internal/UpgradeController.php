<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Models\User;
use App\Models\UpgradeToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UpgradeController extends Controller
{
    /**
     * POST /internal/upgrade/generate-link
     * Generate upgrade link token for user (called from n8n)
     * 
     * Optional: plan parameter to directly link to specific plan checkout
     */
    public function generateLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'plan' => 'nullable|string|in:pro,vip', // Optional: direct to specific plan
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = PhoneHelper::normalize($request->input('phone_number'));
        $plan = $request->input('plan'); // Optional plan parameter

        // Find user by phone number
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', [], 404);
        }

        // Generate upgrade token
        $upgradeToken = UpgradeToken::generateForUser($phone, $user->id);

        // Build upgrade URL
        $baseUrl = env('FRONTEND_URL', 'https://catatuang.click');
        
        // If plan is specified, go directly to checkout
        if ($plan) {
            $upgradeUrl = "{$baseUrl}/checkout?token={$upgradeToken->token}&plan={$plan}";
        } else {
            // Otherwise, go to upgrade page to select plan
            $upgradeUrl = "{$baseUrl}/upgrade/{$upgradeToken->token}";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $upgradeToken->token,
                'upgrade_url' => $upgradeUrl,
                'plan' => $plan, // Return plan if specified
                'expires_at' => $upgradeToken->expires_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Helper method for error response
     */
    private function errorResponse(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}

