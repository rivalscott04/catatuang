<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * POST /internal/transactions/batch
     * Save multiple transactions for a user (identified by phone_number only).
     */
    public function batch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'transactions' => 'required|array|min:1|max:100',
            'transactions.*.tanggal' => 'required|date',
            'transactions.*.amount' => 'required|integer',
            'transactions.*.description' => 'required|string|max:500',
            'transactions.*.category' => 'nullable|string|max:50|in:Makan,Minuman,Transport,Belanja,Hiburan,Kesehatan,Tagihan,Lainnya',
            'transactions.*.type' => 'required|in:income,expense',
            'transactions.*.source' => 'nullable|in:text,receipt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $this->normalizePhoneNumber($request->input('phone_number'));

        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number',
                'errors' => ['phone_number' => ['Phone number is required']],
            ], 422);
        }

        // Use database transaction to prevent race conditions
        $user = DB::transaction(function () use ($phone) {
            // Try to find existing user first (with lock to prevent race condition)
            $user = User::where('phone_number', $phone)->lockForUpdate()->first();

            if ($user) {
                return $user;
            }

            // User doesn't exist, create new one
            // Use try-catch to handle potential race condition if two requests come simultaneously
            try {
                $user = User::create([
                    'phone_number' => $phone,
                    'plan' => 'free',
                    'status' => 'active',
                    'reminder_enabled' => true,
                    'response_style' => 'santai',
                ]);

                // Initialize subscription for new user
                $user->initializeSubscription('free');
                $user->refresh();

                return $user;
            } catch (\Illuminate\Database\QueryException $e) {
                // Handle duplicate key error (race condition)
                // Error code 23000 is for integrity constraint violation (unique constraint)
                if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                    // Another request created the user, fetch it
                    $user = User::where('phone_number', $phone)->first();
                    return $user;
                }
                // Re-throw if it's a different error
                throw $e;
            }
        });

        $payload = $request->input('transactions');

        // Count how many struk (receipt) transactions
        $strukCount = 0;
        foreach ($payload as $item) {
            if (($item['source'] ?? 'text') === 'receipt') {
                $strukCount++;
            }
        }

        // Check struk limit if there are receipt transactions
        if ($strukCount > 0) {
            $canUseStruk = $user->canUseStruk();
            
            if (!$canUseStruk['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Struk limit exceeded',
                    'errors' => [
                        'struk_limit' => [
                            'Limit struk bulanan sudah tercapai. Limit: ' . $canUseStruk['limit'] . ', Terpakai: ' . $canUseStruk['used'],
                        ],
                    ],
                    'data' => [
                        'phone_number' => $user->phone_number,
                        'plan' => $user->plan,
                        'limit_exceeded' => true,
                        'limits' => [
                            'struk' => $canUseStruk,
                        ],
                    ],
                ], 429);
            }

            // Check if adding these struk would exceed limit
            if ($canUseStruk['remaining'] !== null && $strukCount > $canUseStruk['remaining']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Struk limit akan terlampaui',
                    'errors' => [
                        'struk_limit' => [
                            'Mencoba menambah ' . $strukCount . ' struk, tapi sisa limit hanya ' . $canUseStruk['remaining'] . '. Limit: ' . $canUseStruk['limit'] . ', Terpakai: ' . $canUseStruk['used'],
                        ],
                    ],
                    'data' => [
                        'phone_number' => $user->phone_number,
                        'plan' => $user->plan,
                        'limit_exceeded' => true,
                        'limits' => [
                            'struk' => $canUseStruk,
                        ],
                    ],
                ], 429);
            }
        }

        $created = [];

        DB::transaction(function () use ($user, $payload, &$created, $strukCount) {
            foreach ($payload as $item) {
                $tx = Transaction::create([
                    'user_id' => $user->id,
                    'tanggal' => $item['tanggal'],
                    'amount' => $item['amount'],
                    'description' => $item['description'],
                    'category' => $item['category'] ?? 'Lainnya',
                    'type' => $item['type'],
                    'source' => $item['source'] ?? 'text',
                ]);
                $created[] = $tx->id;
            }

            // Increment struk count if there are receipt transactions
            if ($strukCount > 0) {
                for ($i = 0; $i < $strukCount; $i++) {
                    $user->incrementStruk();
                }
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'created_count' => count($created),
                'transaction_ids' => $created,
                'phone_number' => $user->phone_number,
            ],
        ], 201);
    }

    private function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return $phone;
        }

        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($phone, '+62')) {
            return $phone;
        }

        if (str_starts_with($phone, '62')) {
            return '+' . $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '8')) {
            return '+62' . $phone;
        }

        return $phone;
    }
}

