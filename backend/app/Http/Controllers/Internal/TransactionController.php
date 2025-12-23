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

        $user = User::firstOrCreate(
            ['phone_number' => $phone],
            [
                'plan' => 'free',
                'status' => 'active',
                'reminder_enabled' => true,
                'is_unlimited' => false,
                'response_style' => 'santai',
            ]
        );

        $payload = $request->input('transactions');

        $created = [];

        DB::transaction(function () use ($user, $payload, &$created) {
            foreach ($payload as $item) {
                $tx = Transaction::create([
                    'user_id' => $user->id,
                    'tanggal' => $item['tanggal'],
                    'amount' => $item['amount'],
                    'description' => $item['description'],
                    'type' => $item['type'],
                    'source' => $item['source'] ?? 'text',
                ]);
                $created[] = $tx->id;
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

