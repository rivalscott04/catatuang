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
            // Get all pricings and filter active ones
            // Handle both boolean true and integer 1 for is_active
            $allPricings = Pricing::all();
            
            $pricings = $allPricings
                ->filter(function($pricing) {
                    // Check if is_active is true (handle both boolean and integer)
                    $isActive = $pricing->is_active;
                    return $isActive === true || $isActive === 1 || $isActive === '1';
                })
                ->sortBy(function($pricing) {
                    // Sort by plan order: free, pro, vip
                    $order = ['free' => 1, 'pro' => 2, 'vip' => 3];
                    return $order[$pricing->plan] ?? 999;
                })
                ->values()
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
