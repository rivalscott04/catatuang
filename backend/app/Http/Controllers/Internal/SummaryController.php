<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class SummaryController extends Controller
{
    /**
     * GET /internal/summary/today
     * Return aggregate summary for today's transactions.
     */
    public function today(): JsonResponse
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        $query = Transaction::whereDate('tanggal', $today);

        $totalIncome = (clone $query)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');
        $count = (clone $query)->count();

        return response()->json([
            'success' => true,
            'date' => $today,
            'data' => [
                'transactions_count' => $count,
                'total_income' => (int) $totalIncome,
                'total_expense' => (int) $totalExpense,
                'net' => (int) ($totalIncome - $totalExpense),
            ],
        ]);
    }
}

