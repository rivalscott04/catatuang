<?php

namespace App\Http\Controllers;

use App\Models\Pricing;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    /**
     * Get all active pricings for public display
     */
    public function index()
    {
        $pricings = Pricing::where('is_active', true)
            ->orderByRaw("FIELD(plan, 'free', 'pro', 'vip')")
            ->get()
            ->map(function ($pricing) {
                // Ensure features is an array
                $features = $pricing->features;
                if (is_string($features)) {
                    $features = json_decode($features, true) ?? [];
                }
                if (!is_array($features)) {
                    $features = [];
                }
                
                return [
                    'id' => $pricing->id,
                    'plan' => $pricing->plan,
                    'price' => $pricing->price,
                    'description' => $pricing->description,
                    'features' => $features,
                    'is_active' => $pricing->is_active,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pricings,
        ]);
    }
}
