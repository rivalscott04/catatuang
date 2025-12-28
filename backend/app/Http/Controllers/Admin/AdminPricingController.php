<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPricingController extends Controller
{
    /**
     * Get all pricings
     */
    public function index()
    {
        $pricings = Pricing::orderBy('display_order', 'asc')
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
                    'price' => $pricing->price,
                    'description' => $pricing->description,
                    'features' => $features,
                    'is_active' => $pricing->is_active,
                    'display_order' => $pricing->display_order ?? 0,
                    'show_on_main' => $pricing->show_on_main ?? true,
                    'badge_text' => $pricing->badge_text,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pricings,
        ]);
    }

    /**
     * Create new pricing
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan' => 'required|string|max:255|unique:pricings,plan',
            'price' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:255',
            'features' => 'nullable|array',
            'display_order' => 'nullable|integer|min:0',
            'show_on_main' => 'boolean',
            'badge_text' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pricing = Pricing::create([
            'plan' => $request->input('plan'),
            'price' => $request->input('price'),
            'is_active' => $request->input('is_active', true),
            'description' => $request->input('description'),
            'features' => $request->input('features', []),
            'display_order' => $request->input('display_order', 0),
            'show_on_main' => $request->input('show_on_main', true),
            'badge_text' => $request->input('badge_text'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pricing created successfully',
            'data' => $pricing,
        ], 201);
    }

    /**
     * Update pricing
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:255',
            'features' => 'nullable|array',
            'display_order' => 'nullable|integer|min:0',
            'show_on_main' => 'boolean',
            'badge_text' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pricing = Pricing::find($id);

        if (!$pricing) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing not found',
            ], 404);
        }

        $updateData = [
            'price' => $request->input('price'),
            'is_active' => $request->input('is_active', $pricing->is_active),
            'description' => $request->input('description', $pricing->description),
        ];

        if ($request->has('features')) {
            $updateData['features'] = $request->input('features');
        }

        if ($request->has('display_order')) {
            $updateData['display_order'] = $request->input('display_order', 0);
        }

        if ($request->has('show_on_main')) {
            $updateData['show_on_main'] = $request->input('show_on_main', true);
        }

        if ($request->has('badge_text')) {
            $updateData['badge_text'] = $request->input('badge_text');
        }

        $pricing->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Pricing updated successfully',
            'data' => $pricing,
        ]);
    }

    /**
     * Get pricing by plan
     */
    public function show($plan)
    {
        $pricing = Pricing::where('plan', $plan)->first();

        if (!$pricing) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    /**
     * Delete pricing
     */
    public function destroy($id)
    {
        $pricing = Pricing::find($id);

        if (!$pricing) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing not found',
            ], 404);
        }

        // Prevent deletion of essential plans (optional safety check)
        // You can remove this if you want full flexibility
        $essentialPlans = ['free', 'pro', 'vip'];
        if (in_array($pricing->plan, $essentialPlans)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete essential plans (free, pro, vip). You can deactivate them instead.',
            ], 422);
        }

        $pricing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pricing deleted successfully',
        ]);
    }
}

