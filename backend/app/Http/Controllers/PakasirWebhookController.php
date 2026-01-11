<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UpgradeToken;
use App\Models\Pricing;
use App\Models\Payment;

class PakasirWebhookController extends Controller
{
    /**
     * POST /api/webhook/pakasir
     * Handle webhook callback from Pakasir payment gateway
     * 
     * Webhook payload from Pakasir:
     * {
     *   "amount": 22000,
     *   "order_id": "240910HDE7C9",
     *   "project": "depodomain",
     *   "status": "completed",
     *   "payment_method": "qris",
     *   "completed_at": "2024-09-10T08:07:02.819+07:00"
     * }
     */
    public function handle(Request $request): JsonResponse
    {
        // Log incoming webhook for debugging
        Log::info('Pakasir webhook received', [
            'ip' => $request->ip(),
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        // Validate webhook payload
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer|min:1',
            'order_id' => 'required|string|max:255',
            'project' => 'required|string|max:100',
            'status' => 'required|string|in:completed,pending,failed,cancelled',
            'payment_method' => 'required|string|max:50',
            'completed_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            Log::warning('Pakasir webhook validation failed', [
                'errors' => $validator->errors()->toArray(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $amount = $request->input('amount');
        $orderId = $request->input('order_id');
        $project = $request->input('project');
        $status = $request->input('status');
        $paymentMethod = $request->input('payment_method');
        $completedAt = $request->input('completed_at');

        // Only process completed payments
        if ($status !== 'completed') {
            Log::info('Pakasir webhook: Payment not completed', [
                'order_id' => $orderId,
                'status' => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment status is not completed, no action taken',
                'status' => $status,
            ], 200);
        }

        // Verify project slug matches (optional security check)
        $expectedProject = env('PAKASIR_PROJECT_SLUG');
        if ($expectedProject && $project !== $expectedProject) {
            Log::warning('Pakasir webhook: Project mismatch', [
                'expected' => $expectedProject,
                'received' => $project,
                'order_id' => $orderId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid project',
            ], 400);
        }

        try {
            // Process payment in database transaction
            DB::beginTransaction();

            // First, try to find payment by order_id
            $payment = Payment::findByOrderId($orderId);

            if ($payment) {
                // Payment record found - check if already processed
                if ($payment->status === 'completed') {
                    DB::commit();
                    Log::info('Pakasir webhook: Payment already processed', [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment already processed',
                        'data' => [
                            'order_id' => $orderId,
                            'payment_id' => $payment->id,
                        ],
                    ], 200);
                }

                // Update payment record
                $payment->markAsCompleted(
                    $paymentMethod,
                    $orderId, // Pakasir might return different order_id
                    [
                        'webhook_payload' => $request->all(),
                        'completed_at' => $completedAt,
                    ]
                );

                $user = $payment->user;
                $upgradeToken = $payment->upgradeToken;

                if (!$user) {
                    DB::rollBack();
                    Log::error('Pakasir webhook: User not found for payment', [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'User not found',
                    ], 404);
                }

                // Validate plan upgrade
                $planHierarchy = ['free' => 0, 'starter' => 1, 'pro' => 2, 'vip' => 3, 'unlimited' => 999];
                $currentPlanLevel = $planHierarchy[$user->plan] ?? 0;
                $newPlanLevel = $planHierarchy[$payment->plan] ?? 0;

                // Allow upgrade from free to any plan, or to higher tier plan
                if ($currentPlanLevel >= $newPlanLevel && $user->plan !== 'free') {
                    DB::rollBack();
                    Log::warning('Pakasir webhook: Invalid plan upgrade', [
                        'order_id' => $orderId,
                        'current_plan' => $user->plan,
                        'new_plan' => $payment->plan,
                        'user_id' => $user->id,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid plan upgrade',
                    ], 400);
                }

                // Process upgrade
                $oldPlan = $user->plan;
                $user->plan = $payment->plan;
                $user->initializeSubscription($payment->plan);
                $user->save();

                // Mark token as used if exists
                if ($upgradeToken) {
                    $upgradeToken->markAsUsed();
                }

                DB::commit();

                Log::info('Pakasir webhook: Payment processed and upgrade completed', [
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'old_plan' => $oldPlan,
                    'new_plan' => $payment->plan,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed and upgrade completed',
                    'data' => [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                        'user_id' => $user->id,
                        'old_plan' => $oldPlan,
                        'new_plan' => $payment->plan,
                        'amount' => $amount,
                    ],
                ], 200);
            }

            // Fallback: Try to find payment record or upgrade token by order_id (backward compatibility)
            // Format order_id might be: "UPGRADE_{token}" or custom format
            $upgradeToken = null;
            $user = null;

            // Check if order_id is an upgrade token (64 chars)
            if (strlen($orderId) === 64) {
                // Might be a direct token
                $upgradeToken = UpgradeToken::where('token', $orderId)
                    ->whereNull('used_at')
                    ->first();
            } else {
                // Check if order_id follows pattern like "UPGRADE_{token}"
                if (str_starts_with($orderId, 'UPGRADE_')) {
                    // Extract token from order_id format: UPGRADE_{timestamp}_{random}
                    // For backward compatibility, try to find by token if it's in the format
                    // But new format doesn't include token, so skip this
                }
            }

            // If upgrade token found (backward compatibility), process upgrade
            if ($upgradeToken) {
                $user = User::find($upgradeToken->user_id);

                if (!$user) {
                    DB::rollBack();
                    Log::error('Pakasir webhook: User not found for upgrade token', [
                        'order_id' => $orderId,
                        'token' => $upgradeToken->token,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'User not found',
                    ], 404);
                }

                // Find pricing based on amount
                $pricing = Pricing::where('price', $amount)
                    ->where('is_active', true)
                    ->first();

                if (!$pricing) {
                    DB::rollBack();
                    Log::error('Pakasir webhook: Pricing not found for amount', [
                        'order_id' => $orderId,
                        'amount' => $amount,
                        'user_id' => $user->id,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Pricing not found for this amount',
                    ], 404);
                }

                // Validate plan upgrade
                $planHierarchy = ['free' => 0, 'starter' => 1, 'pro' => 2, 'vip' => 3, 'unlimited' => 999];
                $currentPlanLevel = $planHierarchy[$user->plan] ?? 0;
                $newPlanLevel = $planHierarchy[$pricing->plan] ?? 0;

                // Allow upgrade from free to any plan, or to higher tier plan
                if ($currentPlanLevel >= $newPlanLevel && $user->plan !== 'free') {
                    DB::rollBack();
                    Log::warning('Pakasir webhook: Invalid plan upgrade', [
                        'order_id' => $orderId,
                        'current_plan' => $user->plan,
                        'new_plan' => $pricing->plan,
                        'user_id' => $user->id,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid plan upgrade',
                    ], 400);
                }

                // Create payment record for backward compatibility
                $payment = Payment::createPayment([
                    'order_id' => $orderId,
                    'user_id' => $user->id,
                    'upgrade_token_id' => $upgradeToken->id,
                    'plan' => $pricing->plan,
                    'amount' => $amount,
                    'fee' => 0,
                    'total_payment' => $amount,
                    'status' => 'completed',
                    'payment_method' => $paymentMethod,
                    'pakasir_order_id' => $orderId,
                    'completed_at' => $completedAt ? now()->parse($completedAt) : now(),
                    'metadata' => ['webhook_payload' => $request->all()],
                ]);

                // Process upgrade
                $oldPlan = $user->plan;
                $user->plan = $pricing->plan;
                $user->initializeSubscription($pricing->plan);
                $user->save();

                // Mark token as used
                $upgradeToken->markAsUsed();

                DB::commit();

                Log::info('Pakasir webhook: Upgrade processed successfully (backward compatibility)', [
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'old_plan' => $oldPlan,
                    'new_plan' => $pricing->plan,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed and upgrade completed',
                    'data' => [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                        'user_id' => $user->id,
                        'old_plan' => $oldPlan,
                        'new_plan' => $pricing->plan,
                        'amount' => $amount,
                    ],
                ], 200);
            }

            // If no payment or upgrade token found, just log the payment
            DB::commit();

            Log::info('Pakasir webhook: Payment received but no matching order found', [
                'order_id' => $orderId,
                'amount' => $amount,
                'status' => $status,
                'payment_method' => $paymentMethod,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook received and logged',
                'data' => [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'status' => $status,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Pakasir webhook: Processing error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook',
            ], 500);
        }
    }

    /**
     * GET /api/webhook/pakasir
     * Health check endpoint for webhook URL verification
     */
    public function healthCheck(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Pakasir webhook endpoint is active',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}
