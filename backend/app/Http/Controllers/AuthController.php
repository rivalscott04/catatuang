<?php

namespace App\Http\Controllers;

use App\Helpers\PhoneHelper;
use App\Models\User;
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
        $plan = $request->plan ?? 'free';
        if (!in_array($plan, ['free', 'pro', 'vip'])) {
            $plan = 'free';
        }

        // Use database transaction to prevent race conditions
        try {
            $user = \Illuminate\Support\Facades\DB::transaction(function () use ($phoneNumber, $request, $plan) {
                // Check if user already exists (with lock to prevent race condition)
                $existingUser = User::where('phone_number', $phoneNumber)->lockForUpdate()->first();
                
                if ($existingUser) {
                    throw new \Illuminate\Validation\ValidationException(
                        \Illuminate\Support\Facades\Validator::make([], [])
                    );
                }

                // Create new user
                $user = User::create([
                    'phone_number' => $phoneNumber,
                    'name' => $request->name,
                    'plan' => $plan,
                    'status' => 'active',
                    'reminder_enabled' => true,
                ]);

                // Initialize subscription for new user
                $user->initializeSubscription($plan);

                return $user;
            });
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
        return response()->json([
            'message' => 'Registrasi berhasil',
            'phone' => $phoneNumber,
        ], 201);
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

