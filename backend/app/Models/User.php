<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'name',
        'plan',
        'status',
        'reminder_enabled',
        'is_unlimited',
        'response_style',
        'chat_count_month',
        'struk_count_month',
        'last_reset_at',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_status',
    ];

    protected $casts = [
        'reminder_enabled' => 'boolean',
        'is_unlimited' => 'boolean',
        'chat_count_month' => 'integer',
        'struk_count_month' => 'integer',
        'last_reset_at' => 'date',
        'subscription_started_at' => 'date',
        'subscription_expires_at' => 'date',
    ];

    /**
     * Get transactions for this user
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if user has transaction today
     */
    public function hasTransactionToday(): bool
    {
        return $this->transactions()
            ->whereDate('tanggal', today())
            ->exists();
    }

    /**
     * Reset monthly counters if needed
     */
    public function resetMonthlyCountersIfNeeded(): void
    {
        $now = now();
        $lastReset = $this->last_reset_at ? now()->parse($this->last_reset_at) : null;

        // Reset if never reset or if it's a new month
        if (!$lastReset || $lastReset->format('Y-m') !== $now->format('Y-m')) {
            $this->update([
                'chat_count_month' => 0,
                'struk_count_month' => 0,
                'last_reset_at' => $now->toDateString(),
            ]);
            $this->refresh();
        }
    }

    /**
     * Get chat limit based on plan
     */
    public function getChatLimit(): ?int
    {
        return match ($this->plan) {
            'free' => 10,
            'pro' => 200,
            'vip' => null, // unlimited
            default => 10, // default to free limit
        };
    }

    /**
     * Get struk limit based on plan
     */
    public function getStrukLimit(): ?int
    {
        return match ($this->plan) {
            'free' => 1,
            'pro' => 50,
            'vip' => 200,
            default => 1, // default to free limit
        };
    }

    /**
     * Check if user can use chat (increment and check)
     */
    public function canUseChat(): array
    {
        $this->resetMonthlyCountersIfNeeded();
        
        $limit = $this->getChatLimit();
        
        // VIP or no limit
        if ($limit === null) {
            return ['allowed' => true, 'remaining' => null, 'limit' => null];
        }

        // Check if limit exceeded
        if ($this->chat_count_month >= $limit) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'limit' => $limit,
                'used' => $this->chat_count_month,
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $limit - $this->chat_count_month,
            'limit' => $limit,
            'used' => $this->chat_count_month,
        ];
    }

    /**
     * Check if user can use struk (increment and check)
     */
    public function canUseStruk(): array
    {
        $this->resetMonthlyCountersIfNeeded();
        
        $limit = $this->getStrukLimit();
        
        // No limit
        if ($limit === null) {
            return ['allowed' => true, 'remaining' => null, 'limit' => null];
        }

        // Check if limit exceeded
        if ($this->struk_count_month >= $limit) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'limit' => $limit,
                'used' => $this->struk_count_month,
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $limit - $this->struk_count_month,
            'limit' => $limit,
            'used' => $this->struk_count_month,
        ];
    }

    /**
     * Increment chat count
     */
    public function incrementChat(): void
    {
        $this->resetMonthlyCountersIfNeeded();
        $this->increment('chat_count_month');
    }

    /**
     * Increment struk count
     */
    public function incrementStruk(): void
    {
        $this->resetMonthlyCountersIfNeeded();
        $this->increment('struk_count_month');
    }

    /**
     * Initialize subscription for new user
     */
    public function initializeSubscription(string $plan): void
    {
        $today = now();
        
        if ($plan === 'free') {
            // Free plan: 3 days trial
            $this->update([
                'subscription_started_at' => $today->toDateString(),
                'subscription_expires_at' => $today->copy()->addDays(3)->toDateString(),
                'subscription_status' => 'active',
            ]);
        } elseif (in_array($plan, ['pro', 'vip'])) {
            // Pro/VIP: 30 days subscription
            $this->update([
                'subscription_started_at' => $today->toDateString(),
                'subscription_expires_at' => $today->copy()->addDays(30)->toDateString(),
                'subscription_status' => 'active',
            ]);
        }
    }

    /**
     * Check if subscription is active
     */
    public function isSubscriptionActive(): bool
    {
        if ($this->subscription_status !== 'active') {
            return false;
        }

        if (!$this->subscription_expires_at) {
            return true; // No expiry date means active
        }

        return now()->parse($this->subscription_expires_at)->isFuture();
    }

    /**
     * Check if subscription is expiring soon
     */
    public function isExpiringSoon(int $days = 2): bool
    {
        if (!$this->subscription_expires_at || $this->subscription_status !== 'active') {
            return false;
        }

        $expiresAt = now()->parse($this->subscription_expires_at);
        $targetDate = now()->addDays($days);

        return $expiresAt->isSameDay($targetDate);
    }

    /**
     * Get days until subscription expires
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->subscription_expires_at) {
            return null;
        }

        $expiresAt = now()->parse($this->subscription_expires_at);
        $days = now()->diffInDays($expiresAt, false);

        return $days >= 0 ? $days : 0;
    }

    /**
     * Check if user can generate PDF report
     * Only Pro and VIP plans can generate PDF reports
     */
    public function canGenerateReport(): array
    {
        // Check subscription active
        if (!$this->isSubscriptionActive()) {
            return [
                'allowed' => false,
                'reason' => 'subscription_expired',
                'message' => 'Subscription Anda sudah expired. Silakan perpanjang subscription untuk menggunakan fitur ini.',
            ];
        }

        // Check plan: must be pro or vip
        if (!in_array($this->plan, ['pro', 'vip'])) {
            return [
                'allowed' => false,
                'reason' => 'plan_not_supported',
                'message' => 'Fitur PDF report hanya tersedia untuk plan Pro dan VIP. Upgrade plan Anda untuk mengakses fitur ini.',
                'current_plan' => $this->plan,
                'required_plans' => ['pro', 'vip'],
            ];
        }

        return [
            'allowed' => true,
            'plan' => $this->plan,
        ];
    }
}
