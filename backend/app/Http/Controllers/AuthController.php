<?php

namespace App\Http\Controllers;

use App\Helpers\PhoneHelper;
use App\Models\User;
use App\Models\UpgradeToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show registration form
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Handle registration
     */
    public function register(Request $request)
    {
        // Normalize phone number
        $phoneNumber = PhoneHelper::normalize($request->phone_number);

        // Validate
        $validator = Validator::make([
            'phone_number' => $phoneNumber,
            'name' => $request->name,
            'plan' => $request->plan,
        ], [
            'phone_number' => 'required|string|min:10|max:20',
            'name' => 'required|string|max:120',
            'plan' => 'nullable|string|in:free,pro,vip',
        ]);

        if ($validator->fails()) {
            // Handle JSON requests
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // Determine plan - default to 'free' if not provided or invalid
        $requestedPlan = $request->plan ?? 'free';
        if (!in_array($requestedPlan, ['free', 'pro', 'vip'])) {
            $requestedPlan = 'free';
        }

        // For paid plans, user will start with 'free' and need to checkout
        $isPaidPlan = in_array($requestedPlan, ['pro', 'vip']);
        $initialPlan = $isPaidPlan ? 'free' : $requestedPlan;

        // Use database transaction to prevent race conditions
        try {
            $result = \Illuminate\Support\Facades\DB::transaction(function () use ($phoneNumber, $request, $initialPlan, $requestedPlan, $isPaidPlan) {
                // Check if user already exists (with lock to prevent race condition)
                $existingUser = User::where('phone_number', $phoneNumber)->lockForUpdate()->first();
                
                if ($existingUser) {
                    throw new \Illuminate\Validation\ValidationException(
                        \Illuminate\Support\Facades\Validator::make([], [])
                    );
                }

                // Create new user with initial plan (free for paid plans, or requested plan for free)
                $user = User::create([
                    'phone_number' => $phoneNumber,
                    'name' => $request->name,
                    'plan' => $initialPlan,
                    'status' => 'active',
                    'reminder_enabled' => true,
                ]);

                // Initialize subscription for new user
                $user->initializeSubscription($initialPlan);

                // Generate upgrade token if paid plan was requested
                $upgradeToken = null;
                if ($isPaidPlan) {
                    $upgradeToken = UpgradeToken::generateForUser($phoneNumber, $user->id);
                }

                return [
                    'user' => $user,
                    'upgrade_token' => $upgradeToken,
                    'requested_plan' => $requestedPlan,
                ];
            });
            
            $user = $result['user'];
            $upgradeToken = $result['upgrade_token'];
            $requestedPlan = $result['requested_plan'];
        } catch (\Illuminate\Validation\ValidationException $e) {
            // User already exists
            $errorMessage = 'Nomor WhatsApp ini sudah terdaftar. Silakan gunakan nomor lain atau hubungi support.';
            
            // Handle JSON requests
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => $errorMessage,
                    'errors' => ['phone_number' => [$errorMessage]],
                ], 422);
            }
            
            return back()
                ->withErrors(['phone_number' => $errorMessage])
                ->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle duplicate key error (race condition or unique constraint violation)
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                $errorMessage = 'Nomor WhatsApp ini sudah terdaftar. Silakan gunakan nomor lain atau hubungi support.';
                
                // Handle JSON requests
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'message' => $errorMessage,
                        'errors' => ['phone_number' => [$errorMessage]],
                    ], 422);
                }
                
                return back()
                    ->withErrors(['phone_number' => $errorMessage])
                    ->withInput();
            }
            // Re-throw if it's a different error
            throw $e;
        }

        // Return JSON response (frontend handles success display)
        $response = [
            'message' => 'Registrasi berhasil',
            'phone' => $phoneNumber,
        ];
        
        // If paid plan was requested, include upgrade token for checkout
        if ($isPaidPlan && $upgradeToken) {
            $response['upgrade_token'] = $upgradeToken->token;
            $response['plan'] = $requestedPlan;
            $response['needs_checkout'] = true;
        }
        
        return response()->json($response, 201);
    }

    /**
     * Get WhatsApp bot number from environment
     */
    public function getBotNumber()
    {
        // Get bot WhatsApp number from env
        $botNumber = env('WHATSAPP_BOT_NUMBER', '6281234567890');
        
        // Format bot number for WhatsApp link (remove + and spaces)
        $botNumberFormatted = preg_replace('/[^0-9]/', '', $botNumber);
        
        return response()->json([
            'bot_number' => $botNumberFormatted,
        ]);
    }

    /**
     * Normalize Indonesian phone number
     * Converts various formats to standard format (08xx or +62xxx)
     */
}

