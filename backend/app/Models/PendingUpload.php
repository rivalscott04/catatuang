<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PendingUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_url',
        'image_path',
        'status',
        'extracted_data',
        'expires_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get user that owns this pending upload
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if pending upload is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Scope to get only pending and not expired uploads
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired uploads
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<=', now());
    }
}






