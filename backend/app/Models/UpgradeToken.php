<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UpgradeToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'token',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the upgrade token
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if token is valid (not expired and not used)
     */
    public function isValid(): bool
    {
        if ($this->used_at !== null) {
            return false;
        }

        return $this->expires_at->isFuture();
    }

    /**
     * Mark token as used
     */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    /**
     * Generate a new upgrade token for user
     */
    public static function generateForUser(string $phoneNumber, ?int $userId = null): self
    {
        // Delete any existing unused tokens for this phone number
        self::where('phone_number', $phoneNumber)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->delete();

        // Generate new token
        $token = Str::random(64);
        $expiresAt = now()->addHour(); // Token expires in 1 hour

        return self::create([
            'user_id' => $userId,
            'phone_number' => $phoneNumber,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Find valid token by token string
     */
    public static function findValid(string $token): ?self
    {
        $upgradeToken = self::where('token', $token)->first();

        if (!$upgradeToken) {
            return null;
        }

        if (!$upgradeToken->isValid()) {
            return null;
        }

        return $upgradeToken;
    }
}

