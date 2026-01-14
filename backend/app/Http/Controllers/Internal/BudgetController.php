<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Models\Budget;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BudgetController extends Controller
{
    /**
     * POST /internal/budget/set
     * Set or update budget for a user (identified by phone_number only).
     * If budget already exists for the month/year, accumulate (add) the amount.
     */
    public function set(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'budget_amount' => 'required|integer|min:0',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = PhoneHelper::normalize($request->input('phone_number'));

        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number',
                'errors' => ['phone_number' => ['Phone number is required']],
            ], 422);
        }

        // Get month and year (default to current month/year)
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $budgetAmount = $request->input('budget_amount');

        // Use database transaction to prevent race conditions
        $result = DB::transaction(function () use ($phone, $month, $year, $budgetAmount) {
            // Find user (with lock to prevent race condition)
            $user = User::where('phone_number', $phone)->lockForUpdate()->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                ];
            }

            // Find existing budget for this month/year
            $budget = Budget::where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($budget) {
                // Budget exists: accumulate (add) the amount
                $budget->increment('budget_amount', $budgetAmount);
                $budget->refresh();
            } else {
                // Budget doesn't exist: create new one
                $budget = Budget::create([
                    'user_id' => $user->id,
                    'month' => $month,
                    'year' => $year,
                    'budget_amount' => $budgetAmount,
                ]);
            }

            return [
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'month' => $budget->month,
                    'year' => $budget->year,
                    'budget_amount' => $budget->budget_amount,
                    'added_amount' => $budgetAmount,
                    'is_new' => $budget->wasRecentlyCreated,
                ],
            ];
        });

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result, 200);
    }

    /**
     * GET /internal/budget/get
     * Get budget for a user for specific month/year (default: current month/year)
     */
    public function get(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = PhoneHelper::normalize($request->input('phone_number'));

        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number',
                'errors' => ['phone_number' => ['Phone number is required']],
            ], 422);
        }

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $budget = Budget::where('user_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'month' => $month,
                'year' => $year,
                'budget_amount' => $budget ? $budget->budget_amount : 0,
                'has_budget' => $budget !== null,
            ],
        ], 200);
    }
}
