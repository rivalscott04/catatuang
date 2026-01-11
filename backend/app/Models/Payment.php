<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'upgrade_token_id',
        'plan',
        'amount',
        'fee',
        'total_payment',
        'status',
        'payment_method',
        'pakasir_order_id',
        'expires_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee' => 'integer',
        'total_payment' => 'integer',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the payment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the upgrade token associated with this payment
     */
    public function upgradeToken(): BelongsTo
    {
        return $this->belongsTo(UpgradeToken::class);
    }

    /**
     * Generate a unique order ID
     */
    public static function generateOrderId(): string
    {
        // Format: UPGRADE_{timestamp}_{random}
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);
        return "UPGRADE_{$timestamp}_{$random}";
    }

    /**
     * Create a new payment record
     */
    public static function createPayment(array $data): self
    {
        return self::create([
            'order_id' => $data['order_id'] ?? self::generateOrderId(),
            'user_id' => $data['user_id'],
            'upgrade_token_id' => $data['upgrade_token_id'] ?? null,
            'plan' => $data['plan'],
            'amount' => $data['amount'],
            'fee' => $data['fee'] ?? 0,
            'total_payment' => $data['total_payment'],
            'status' => $data['status'] ?? 'pending',
            'payment_method' => $data['payment_method'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(string $paymentMethod, ?string $pakasirOrderId = null, ?array $metadata = null): void
    {
        $this->update([
            'status' => 'completed',
            'payment_method' => $paymentMethod,
            'pakasir_order_id' => $pakasirOrderId,
            'completed_at' => now(),
            'metadata' => $metadata ? array_merge($this->metadata ?? [], $metadata) : $this->metadata,
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(?array $metadata = null): void
    {
        $this->update([
            'status' => 'failed',
            'metadata' => $metadata ? array_merge($this->metadata ?? [], $metadata) : $this->metadata,
        ]);
    }

    /**
     * Mark payment as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Check if payment is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast() && $this->status === 'pending';
    }

    /**
     * Find payment by order_id
     */
    public static function findByOrderId(string $orderId): ?self
    {
        return self::where('order_id', $orderId)->first();
    }
}
