<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin;

class AdminAuthController extends Controller
{
    /**
     * Handle admin login with anti-bot protection
     */
    public function login(Request $request)
    {
        // Anti-bot: Honeypot field - if filled, it's likely a bot
        if ($request->has('website') && !empty($request->input('website'))) {
            // Bot detected, return generic error without processing
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }
        
        // Anti-bot: Check if request is too fast (less than 1 second from page load)
        // This is handled by frontend timestamp, but we validate it here too
        $timestamp = $request->input('_timestamp');
        if ($timestamp && (time() - (int)$timestamp) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }
        
        // Anti-bot: Validate user agent exists (basic check)
        if (!$request->hasHeader('User-Agent') || empty($request->header('User-Agent'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('username', 'password');
        $loginInput = $credentials['username'];
        
        // Try to find admin by username or email
        $admin = Admin::where(function ($query) use ($loginInput) {
                $query->where('username', $loginInput)
                      ->orWhere('email', $loginInput);
            })
            ->where('is_active', true)
            ->first();

        if (!$admin) {
            // Always return same error message to prevent username enumeration
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check password
        if (!password_verify($credentials['password'], $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Login admin
        Auth::guard('admin')->login($admin);
        
        // Update last login
        $admin->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'admin' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'name' => $admin->name,
                    'email' => $admin->email,
                ],
            ],
        ]);
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Get current admin
     */
    public function me()
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'admin' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'name' => $admin->name,
                    'email' => $admin->email,
                ],
            ],
        ]);
    }

    /**
     * Update admin password
     */
    public function updatePassword(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                function ($attribute, $value, $fail) {
                    // Check for at least one capital letter
                    if (!preg_match('/[A-Z]/', $value)) {
                        $fail('Password harus mengandung minimal 1 huruf kapital.');
                    }
                    // Check for at least one symbol (common special characters)
                    if (!preg_match('/[!@#$%^&*(),.?":{}|<>\[\]\\/_+\-=~`]/', $value)) {
                        $fail('Password harus mengandung minimal 1 simbol.');
                    }
                    // Check for common weak passwords
                    $weakPasswords = ['admin123', 'password123', 'admin', 'password', '12345678', 'qwerty123'];
                    if (in_array(strtolower($value), array_map('strtolower', $weakPasswords))) {
                        $fail('Password terlalu lemah. Gunakan password yang lebih kuat.');
                    }
                },
            ],
            'new_password_confirmation' => 'required|string|same:new_password',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password minimal 8 karakter.',
            'new_password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            'new_password_confirmation.same' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify current password
        if (!password_verify($request->current_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini tidak benar.',
                'errors' => [
                    'current_password' => ['Password saat ini tidak benar.'],
                ],
            ], 422);
        }

        // Check if new password is same as current password
        if (password_verify($request->new_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password baru harus berbeda dengan password saat ini.',
                'errors' => [
                    'new_password' => ['Password baru harus berbeda dengan password saat ini.'],
                ],
            ], 422);
        }

        // Update password
        $admin->password = $request->new_password;
        $admin->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
        ]);
    }

    /**
     * Update admin username
     */
    public function updateUsername(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:admins,username,' . $admin->id,
            ],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal 3 karakter.',
            'username.max' => 'Username maksimal 50 karakter.',
            'username.regex' => 'Username hanya boleh mengandung huruf, angka, dan underscore (_).',
            'username.unique' => 'Username sudah digunakan. Silakan pilih username lain.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if new username is same as current username
        if ($request->username === $admin->username) {
            return response()->json([
                'success' => false,
                'message' => 'Username baru harus berbeda dengan username saat ini.',
                'errors' => [
                    'username' => ['Username baru harus berbeda dengan username saat ini.'],
                ],
            ], 422);
        }

        // Update username
        $admin->username = $request->username;
        $admin->save();

        return response()->json([
            'success' => true,
            'message' => 'Username berhasil diperbarui.',
            'data' => [
                'admin' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'name' => $admin->name,
                    'email' => $admin->email,
                ],
            ],
        ]);
    }
}

