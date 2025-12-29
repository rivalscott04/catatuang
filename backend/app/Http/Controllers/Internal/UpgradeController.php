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
     */
    public function generateLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = PhoneHelper::normalize($request->input('phone_number'));

        // Find user by phone number
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', [], 404);
        }

        // Generate upgrade token
        $upgradeToken = UpgradeToken::generateForUser($phone, $user->id);

        // Build upgrade URL
        $baseUrl = env('FRONTEND_URL', 'https://catatuang.click');
        $upgradeUrl = "{$baseUrl}/upgrade/{$upgradeToken->token}";

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $upgradeToken->token,
                'upgrade_url' => $upgradeUrl,
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

