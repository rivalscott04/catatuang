<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * POST /internal/users/check-or-create
     * Resolve user by phone_number; create if missing.
     */
    public function checkOrCreate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'name' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = $this->normalizePhoneNumber($request->input('phone_number'));
        $name = $request->input('name');

        $wasRecentlyCreated = false;
        $user = User::firstOrCreate(
            ['phone_number' => $phone],
            [
                'name' => $name,
                'plan' => 'free',
                'status' => 'active',
                'reminder_enabled' => true,
                'is_unlimited' => false,
                'response_style' => 'santai',
            ]
        );

        // Check if user was just created
        if ($user->wasRecentlyCreated) {
            $wasRecentlyCreated = true;
            // Initialize subscription for new user
            $user->initializeSubscription('free');
            $user->refresh();
        }

        // If user exists and name provided, update nullable name
        if ($name && !$user->name) {
            $user->update(['name' => $name]);
        }

        // Get limit info
        $chatLimit = $user->getChatLimit();
        $strukLimit = $user->getStrukLimit();
        $user->resetMonthlyCountersIfNeeded();

        return response()->json([
            'success' => true,
            'data' => [
                'phone_number' => $user->phone_number,
                'response_style' => $user->response_style,
                'reminder_enabled' => $user->reminder_enabled,
                'status' => $user->status,
                'plan' => $user->plan,
                'limits' => [
                    'chat' => [
                        'limit' => $chatLimit,
                        'used' => $user->chat_count_month,
                        'remaining' => $chatLimit === null ? null : max(0, $chatLimit - $user->chat_count_month),
                    ],
                    'struk' => [
                        'limit' => $strukLimit,
                        'used' => $user->struk_count_month,
                        'remaining' => $strukLimit === null ? null : max(0, $strukLimit - $user->struk_count_month),
                    ],
                ],
            ],
        ]);
    }

    /**
     * POST /internal/users/reminder
     * Toggle reminder_enabled for a user.
     */
    public function updateReminder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'reminder_enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = $this->normalizePhoneNumber($request->input('phone_number'));
        $enabled = (bool) $request->boolean('reminder_enabled');

        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        $user->update(['reminder_enabled' => $enabled]);

        return response()->json([
            'success' => true,
            'data' => [
                'phone_number' => $user->phone_number,
                'reminder_enabled' => $user->reminder_enabled,
                'response_style' => $user->response_style,
            ],
        ]);
    }

    /**
     * POST /internal/users/style
     * Set response style via command (santai/netral/formal/gaul/biasa->netral).
     */
    public function updateStyle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'style' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = $this->normalizePhoneNumber($request->input('phone_number'));
        $styleInput = strtolower(trim($request->input('style')));

        $styleMap = [
            'santai' => 'santai',
            'netral' => 'netral',
            'biasa' => 'netral',
            'formal' => 'formal',
            'gaul' => 'gaul',
        ];

        if (!array_key_exists($styleInput, $styleMap)) {
            return $this->errorResponse('Invalid style', ['style' => ['Allowed: santai, netral/biasa, formal, gaul']], 422);
        }

        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        $user->update(['response_style' => $styleMap[$styleInput]]);

        return response()->json([
            'success' => true,
            'data' => [
                'phone_number' => $user->phone_number,
                'response_style' => $user->response_style,
            ],
        ]);
    }

    private function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return $phone;
        }

        // Remove spaces/dashes except leading +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($phone, '+62')) {
            return $phone;
        }

        if (str_starts_with($phone, '62')) {
            return '+' . $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '8')) {
            return '+62' . $phone;
        }

        return $phone;
    }

    /**
     * POST /internal/users/increment-chat
     * Increment chat count for a user (called by n8n on each incoming message).
     */
    public function incrementChat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = $this->normalizePhoneNumber($request->input('phone_number'));
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        // Check limit before incrementing
        $canUse = $user->canUseChat();
        
        if (!$canUse['allowed']) {
            return response()->json([
                'success' => false,
                'message' => 'Chat limit exceeded',
                'data' => [
                    'phone_number' => $user->phone_number,
                    'plan' => $user->plan,
                    'limit_exceeded' => true,
                    'limits' => [
                        'chat' => $canUse,
                    ],
                ],
            ], 429);
        }

        // Increment chat count
        $user->incrementChat();
        $user->refresh();

        // Get updated limit info
        $chatLimit = $user->getChatLimit();
        $strukLimit = $user->getStrukLimit();

        return response()->json([
            'success' => true,
            'data' => [
                'phone_number' => $user->phone_number,
                'plan' => $user->plan,
                'limits' => [
                    'chat' => [
                        'limit' => $chatLimit,
                        'used' => $user->chat_count_month,
                        'remaining' => $chatLimit === null ? null : max(0, $chatLimit - $user->chat_count_month),
                    ],
                    'struk' => [
                        'limit' => $strukLimit,
                        'used' => $user->struk_count_month,
                        'remaining' => $strukLimit === null ? null : max(0, $strukLimit - $user->struk_count_month),
                    ],
                ],
            ],
        ]);
    }

    /**
     * GET /internal/users/limits
     * Get current limit status for a user.
     */
    public function getLimits(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = $this->normalizePhoneNumber($request->input('phone_number'));
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        $user->resetMonthlyCountersIfNeeded();
        $chatLimit = $user->getChatLimit();
        $strukLimit = $user->getStrukLimit();

        return response()->json([
            'success' => true,
            'data' => [
                'phone_number' => $user->phone_number,
                'plan' => $user->plan,
                'limits' => [
                    'chat' => [
                        'limit' => $chatLimit,
                        'used' => $user->chat_count_month,
                        'remaining' => $chatLimit === null ? null : max(0, $chatLimit - $user->chat_count_month),
                    ],
                    'struk' => [
                        'limit' => $strukLimit,
                        'used' => $user->struk_count_month,
                        'remaining' => $strukLimit === null ? null : max(0, $strukLimit - $user->struk_count_month),
                    ],
                ],
            ],
        ]);
    }

    private function errorResponse(string $message, $errors, int $code): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}

