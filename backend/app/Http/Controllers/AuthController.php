<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
        $phoneNumber = $this->normalizePhoneNumber($request->phone_number);

        // Validate
        $validator = Validator::make([
            'phone_number' => $phoneNumber,
            'name' => $request->name,
            'plan' => $request->plan,
        ], [
            'phone_number' => 'required|string|min:10|max:20',
            'name' => 'nullable|string|max:120',
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

        // Check if user already exists
        $existingUser = User::where('phone_number', $phoneNumber)->first();
        
        if ($existingUser) {
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

        // Determine plan - default to 'free' if not provided or invalid
        $plan = $request->plan ?? 'free';
        if (!in_array($plan, ['free', 'pro', 'vip'])) {
            $plan = 'free';
        }

        // Create new user
        $user = User::create([
            'phone_number' => $phoneNumber,
            'name' => $request->name ?? null,
            'plan' => $plan,
            'status' => 'active',
            'reminder_enabled' => true,
            'is_unlimited' => false,
        ]);

        // Initialize subscription for new user
        $user->initializeSubscription($plan);

        // Handle JSON requests
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Registrasi berhasil',
                'phone' => $phoneNumber,
            ], 201);
        }

        // Redirect to success page
        return redirect()->route('register.success', ['phone' => $phoneNumber]);
    }

    /**
     * Show success page after registration
     */
    public function showSuccess(Request $request)
    {
        $phoneNumber = $request->query('phone');
        
        // Get bot WhatsApp number from env or config
        $botNumber = env('WHATSAPP_BOT_NUMBER', '6281234567890'); // Default, should be in .env
        
        // Format bot number for WhatsApp link (remove + and spaces)
        $botNumberFormatted = preg_replace('/[^0-9]/', '', $botNumber);
        
        return view('auth.success', [
            'phoneNumber' => $phoneNumber,
            'botNumber' => $botNumberFormatted,
        ]);
    }

    /**
     * Normalize Indonesian phone number
     * Converts various formats to standard format (08xx or +62xxx)
     */
    private function normalizePhoneNumber($phone)
    {
        if (empty($phone)) {
            return $phone;
        }

        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // If starts with +62, keep it
        if (strpos($phone, '+62') === 0) {
            return $phone;
        }

        // If starts with 62 (without +), add +
        if (strpos($phone, '62') === 0 && strlen($phone) > 2) {
            return '+' . $phone;
        }

        // If starts with 0, convert to +62
        if (strpos($phone, '0') === 0) {
            return '+62' . substr($phone, 1);
        }

        // If starts with 8 (assume it's 08xx), add +62
        if (strpos($phone, '8') === 0) {
            return '+62' . $phone;
        }

        // Default: assume it's already in correct format or add +62
        if (strlen($phone) >= 10) {
            return '+62' . ltrim($phone, '0');
        }

        return $phone;
    }
}

