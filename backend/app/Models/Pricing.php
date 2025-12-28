<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan',
        'price',
        'is_active',
        'description',
        'features',
        'display_order',
        'show_on_main',
        'badge_text',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
        'display_order' => 'integer',
        'show_on_main' => 'boolean',
    ];

    /**
     * Get active pricing for a plan
     */
    public static function getPriceForPlan(string $plan): int
    {
        $pricing = self::where('plan', $plan)
            ->where('is_active', true)
            ->first();

        return $pricing ? $pricing->price : 0;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
}


