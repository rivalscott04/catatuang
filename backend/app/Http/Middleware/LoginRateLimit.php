<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LoginRateLimit
{
    /**
     * Handle an incoming request with enhanced rate limiting and progressive delays.
     * 
     * Rate limiting strategy:
     * - 3 attempts per 15 minutes per IP
     * - Progressive delay after failed attempts (1s, 2s, 5s)
     * - Lockout after 5 failed attempts for 30 minutes
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        
        $key = 'login_attempts:' . $ip;
        $username = $request->input('username');
        $usernameKey = $username ? 'login_attempts_username:' . md5($username) : null;
        
        // Check IP-based rate limit (3 attempts per 15 minutes)
        $ipLimitKey = 'login_ip_limit:' . $ip;
        $ipAttempts = Cache::get($ipLimitKey, 0);
        
        if ($ipAttempts >= 3) {
            $remaining = Cache::get($ipLimitKey . ':reset', now()->addMinutes(15));
            $seconds = max(0, now()->diffInSeconds($remaining, false));
            
            if ($seconds > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.',
                    'retry_after' => $seconds,
                ], 429)->header('Retry-After', $seconds);
            } else {
                // Reset counter if time expired
                Cache::forget($ipLimitKey);
                Cache::forget($ipLimitKey . ':reset');
            }
        }
        
        // Check for lockout (5 failed attempts = 30 minute lockout)
        $lockoutKey = 'login_lockout:' . $ip;
        $lockoutUntil = Cache::get($lockoutKey);
        
        // Track lockout count for blacklist decision
        $lockoutCountKey = 'login_lockout_count:' . $ip;
        $lockoutCount = Cache::get($lockoutCountKey, 0);
        
        if ($lockoutUntil && now()->lt($lockoutUntil)) {
            $seconds = now()->diffInSeconds($lockoutUntil, false);
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda terkunci karena terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.',
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', $seconds);
        }
        
        // Get failed attempts count
        $failedAttempts = Cache::get($key, 0);
        
        // Progressive delay based on failed attempts
        if ($failedAttempts > 0) {
            $delay = min($failedAttempts * 1, 5); // Max 5 seconds delay
            usleep($delay * 1000000); // Convert to microseconds
        }
        
        // Process request
        $response = $next($request);
        
        // Check if login failed
        $loginFailed = false;
        if ($response->getStatusCode() === 401) {
            $loginFailed = true;
        } elseif ($response->getStatusCode() === 200) {
            $content = $response->getContent();
            if (is_string($content)) {
                $data = json_decode($content, true);
                if (isset($data['success']) && $data['success'] === false) {
                    $loginFailed = true;
                }
            }
        }
        
        // If login failed, increment counters
        if ($loginFailed) {
            
            // Increment IP-based attempts
            Cache::put($ipLimitKey, $ipAttempts + 1, now()->addMinutes(15));
            Cache::put($ipLimitKey . ':reset', now()->addMinutes(15), now()->addMinutes(15));
            
            // Increment failed attempts
            $newFailedAttempts = $failedAttempts + 1;
            Cache::put($key, $newFailedAttempts, now()->addMinutes(15));
            
            // If username provided, also track by username
            if ($usernameKey) {
                $usernameAttempts = Cache::get($usernameKey, 0);
                Cache::put($usernameKey, $usernameAttempts + 1, now()->addMinutes(15));
            }
            
            // Lockout after 5 failed attempts
            if ($newFailedAttempts >= 5) {
                $newLockoutCount = $lockoutCount + 1;
                Cache::put($lockoutKey, now()->addMinutes(30), now()->addMinutes(30));
                Cache::put($lockoutCountKey, $newLockoutCount, now()->addDays(1)); // Keep count for 24 hours
            }
        } else {
            // Login successful, clear counters
            Cache::forget($key);
            Cache::forget($ipLimitKey);
            Cache::forget($ipLimitKey . ':reset');
            Cache::forget($lockoutKey);
            Cache::forget($lockoutCountKey); // Also clear lockout count on successful login
            if ($usernameKey) {
                Cache::forget($usernameKey);
            }
        }
        
        return $response;
    }
}

