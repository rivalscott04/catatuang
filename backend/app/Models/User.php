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
    ];

    protected $casts = [
        'reminder_enabled' => 'boolean',
        'is_unlimited' => 'boolean',
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
}
