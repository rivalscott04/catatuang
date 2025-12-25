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
     * Handle admin login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
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
}

