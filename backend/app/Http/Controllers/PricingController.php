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
        try {
            // Get pricings that are active AND shown on main page
            // Handle both boolean true and integer 1 for is_active and show_on_main
            $pricings = Pricing::where('is_active', true)
                ->where('show_on_main', true)
                ->orderBy('display_order', 'asc')
                ->orderBy('plan', 'asc')
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
                        'price' => (int) $pricing->price,
                        'description' => $pricing->description ?? '',
                        'features' => $features,
                        'is_active' => (bool) $pricing->is_active,
                        'badge_text' => $pricing->badge_text,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $pricings->all(),
            ], 200, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Exception $e) {
            \Log::error('PricingController error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load pricing data',
                'data' => [],
            ], 500);
        }
    }
}
