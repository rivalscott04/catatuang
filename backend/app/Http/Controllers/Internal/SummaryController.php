<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class SummaryController extends Controller
{
    /**
     * GET /internal/summary/today
     * Return total expense for today's transactions (for "rekap hari ini").
     * Requires phone_number query parameter and active subscription.
     */
    public function today(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = PhoneHelper::normalize($request->input('phone_number'));

        // Get user
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        // Check subscription active
        if (!$user->isSubscriptionActive()) {
            return $this->subscriptionExpiredResponse($user, 'rekap_hari_ini');
        }

        $today = Carbon::now(config('app.timezone'))->toDateString();

        // Query transactions for today, filtered by user
        $query = $user->transactions()->whereDate('tanggal', $today);

        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');

        return response()->json([
            'success' => true,
            'date' => $today,
            'data' => [
                'total_expense' => (int) $totalExpense,
            ],
        ]);
    }

    /**
     * GET /internal/summary/today-detail
     * Return list of today's transactions with details (for "rekap detail").
     * Requires phone_number query parameter and active subscription.
     * Transactions are ordered by created_at DESC (newest first).
     */
    public function todayDetail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = PhoneHelper::normalize($request->input('phone_number'));

        // Get user
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        // Check subscription active
        if (!$user->isSubscriptionActive()) {
            return $this->subscriptionExpiredResponse($user, 'rekap_detail');
        }

        $today = Carbon::now(config('app.timezone'))->toDateString();

        // Query transactions for today, filtered by user, ordered by created_at DESC (newest first)
        $transactions = $user->transactions()
            ->whereDate('tanggal', $today)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'description', 'amount', 'type', 'category', 'tanggal', 'created_at']);

        // Calculate totals
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $totalIncome = $transactions->where('type', 'income')->sum('amount');

        return response()->json([
            'success' => true,
            'date' => $today,
            'data' => [
                'transactions' => $transactions,
                'total_expense' => (int) $totalExpense,
                'total_income' => (int) $totalIncome,
            ],
        ]);
    }

    /**
     * GET /internal/summary/month-balance
     * Return month balance (income - expense) for current month (for "saldo").
     * Requires phone_number query parameter and active subscription.
     */
    public function monthBalance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = PhoneHelper::normalize($request->input('phone_number'));

        // Get user
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        // Check subscription active
        if (!$user->isSubscriptionActive()) {
            return $this->subscriptionExpiredResponse($user, 'cek_saldo');
        }

        $now = Carbon::now(config('app.timezone'));
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        // Query transactions for current month, filtered by user
        $query = $user->transactions()
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth]);

        $totalIncome = (clone $query)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');
        $net = $totalIncome - $totalExpense;

        return response()->json([
            'success' => true,
            'period' => $now->locale('id')->isoFormat('MMMM YYYY'), // "Januari 2024"
            'data' => [
                'total_income' => (int) $totalIncome,
                'total_expense' => (int) $totalExpense,
                'net' => (int) $net,
            ],
        ]);
    }

    /**
     * GET /internal/summary/statistics-by-category
     * Return statistics by category for current month (for "statistik" or "top kategori").
     * Requires phone_number query parameter and active subscription.
     */
    public function statisticsByCategory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = PhoneHelper::normalize($request->input('phone_number'));

        // Get user
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        // Check subscription active
        if (!$user->isSubscriptionActive()) {
            return $this->subscriptionExpiredResponse($user, 'rekap_detail');
        }

        $now = Carbon::now(config('app.timezone'));
        $month = $request->input('month', $now->month);
        $year = $request->input('year', $now->year);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        // Get all expense transactions for the month
        $expenses = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();

        $totalExpense = $expenses->sum('amount');

        // Group by category
        $categoryStats = $expenses->groupBy('category')->map(function ($transactions, $category) use ($totalExpense) {
            $categoryTotal = $transactions->sum('amount');
            $percentage = $totalExpense > 0 ? round(($categoryTotal / $totalExpense) * 100, 1) : 0;
            
            return [
                'category' => $category ?? 'Lainnya',
                'total' => (int) $categoryTotal,
                'count' => $transactions->count(),
                'percentage' => $percentage,
            ];
        })->values()->sortByDesc('total')->values();

        return response()->json([
            'success' => true,
            'period' => Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM YYYY'),
            'data' => [
                'statistics' => $categoryStats,
                'total_expense' => (int) $totalExpense,
                'total_categories' => $categoryStats->count(),
            ],
        ]);
    }

    /**
     * GET /internal/summary/by-category
     * Return transactions filtered by category (for "rekap [kategori]").
     * Requires phone_number and category query parameters and active subscription.
     */
    public function byCategory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:8|max:20',
            'category' => 'required|string|in:Makan,Minuman,Transport,Belanja,Hiburan,Kesehatan,Tagihan,Lainnya',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $phone = PhoneHelper::normalize($request->input('phone_number'));

        // Get user
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return $this->errorResponse('User not found', ['phone_number' => ['User not registered']], 404);
        }

        // Check subscription active
        if (!$user->isSubscriptionActive()) {
            return $this->subscriptionExpiredResponse($user, 'rekap_detail');
        }

        $now = Carbon::now(config('app.timezone'));
        $month = $request->input('month', $now->month);
        $year = $request->input('year', $now->year);
        $category = $request->input('category');

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        // Query transactions filtered by category
        $transactions = $user->transactions()
            ->where('category', $category)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'description', 'amount', 'type', 'category', 'tanggal', 'created_at']);

        $total = $transactions->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $totalIncome = $transactions->where('type', 'income')->sum('amount');

        return response()->json([
            'success' => true,
            'period' => Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM YYYY'),
            'data' => [
                'category' => $category,
                'transactions' => $transactions,
                'total' => (int) $total,
                'total_expense' => (int) $totalExpense,
                'total_income' => (int) $totalIncome,
                'count' => $transactions->count(),
            ],
        ]);
    }

    /**
     * Error response helper
     */
    private function errorResponse(string $message, $errors, int $code): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Subscription expired response with response_style and contextual action
     * 
     * @param User $user
     * @param string $action Action context: 'cek_saldo', 'rekap_hari_ini', 'rekap_detail', 'catat_transaksi'
     * @return JsonResponse
     */
    private function subscriptionExpiredResponse(User $user, string $action = 'cek_saldo'): JsonResponse
    {
        $style = $user->response_style ?? 'santai';

        // Define messages for each action and style
        $messages = [
            'cek_saldo' => [
                'gaul' => 'Eits, subscription kamu udah expired nih. Perpanjang dulu dong biar bisa cek saldo!',
                'santai' => 'Wah, subscription kamu sudah expired nih. Perpanjang dulu yuk biar bisa cek saldo!',
                'netral' => 'Subscription Anda sudah expired. Silakan perpanjang subscription untuk cek saldo.',
                'formal' => 'Maaf, subscription Anda telah berakhir. Mohon perpanjang subscription terlebih dahulu untuk mengakses fitur cek saldo.',
            ],
            'rekap_hari_ini' => [
                'gaul' => 'Eits, subscription kamu udah expired nih. Perpanjang dulu dong biar bisa lihat rekap hari ini!',
                'santai' => 'Wah, subscription kamu sudah expired nih. Perpanjang dulu yuk biar bisa lihat rekap hari ini!',
                'netral' => 'Subscription Anda sudah expired. Silakan perpanjang subscription untuk melihat rekap hari ini.',
                'formal' => 'Maaf, subscription Anda telah berakhir. Mohon perpanjang subscription terlebih dahulu untuk mengakses fitur rekap hari ini.',
            ],
            'rekap_detail' => [
                'gaul' => 'Eits, subscription kamu udah expired nih. Perpanjang dulu dong biar bisa lihat rekap detail!',
                'santai' => 'Wah, subscription kamu sudah expired nih. Perpanjang dulu yuk biar bisa lihat rekap detail!',
                'netral' => 'Subscription Anda sudah expired. Silakan perpanjang subscription untuk melihat rekap detail.',
                'formal' => 'Maaf, subscription Anda telah berakhir. Mohon perpanjang subscription terlebih dahulu untuk mengakses fitur rekap detail.',
            ],
            'catat_transaksi' => [
                'gaul' => 'Eits, subscription kamu udah expired nih. Perpanjang dulu dong biar bisa catat transaksi!',
                'santai' => 'Wah, subscription kamu sudah expired nih. Perpanjang dulu yuk biar bisa catat transaksi!',
                'netral' => 'Subscription Anda sudah expired. Silakan perpanjang subscription untuk mencatat transaksi.',
                'formal' => 'Maaf, subscription Anda telah berakhir. Mohon perpanjang subscription terlebih dahulu untuk mengakses fitur pencatatan transaksi.',
            ],
        ];

        // Get message for the specific action and style, fallback to netral if not found
        $message = $messages[$action][$style] ?? $messages[$action]['netral'] ?? $messages['cek_saldo']['netral'];

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => [
                'subscription' => ['Subscription expired'],
            ],
            'data' => [
                'current_plan' => $user->plan,
                'response_style' => $style,
                'reason' => 'subscription_expired',
                'action' => $action,
            ],
        ], 403);
    }
}

