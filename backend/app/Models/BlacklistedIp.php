<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'lockout_count',
        'total_failed_attempts',
        'blacklisted_at',
    ];

    protected $casts = [
        'blacklisted_at' => 'datetime',
    ];

    /**
     * Check if an IP is blacklisted
     */
    public static function isBlacklisted(string $ip): bool
    {
        return self::where('ip_address', $ip)->exists();
    }

    /**
     * Get blacklisted IP record
     */
    public static function getBlacklisted(string $ip): ?self
    {
        return self::where('ip_address', $ip)->first();
    }

    /**
     * Add IP to blacklist
     */
    public static function addToBlacklist(
        string $ip,
        int $lockoutCount,
        int $totalFailedAttempts,
        ?string $reason = null
    ): self {
        return self::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason ?? "Terlalu banyak percobaan login yang gagal ({$totalFailedAttempts} percobaan, {$lockoutCount} kali lockout)",
                'lockout_count' => $lockoutCount,
                'total_failed_attempts' => $totalFailedAttempts,
                'blacklisted_at' => now(),
            ]
        );
    }
}

