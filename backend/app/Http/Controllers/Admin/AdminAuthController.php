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
}

