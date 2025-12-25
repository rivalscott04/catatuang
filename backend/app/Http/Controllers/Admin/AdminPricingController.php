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
        $pricings = Pricing::orderBy('plan')->get()->map(function ($pricing) {
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
}

