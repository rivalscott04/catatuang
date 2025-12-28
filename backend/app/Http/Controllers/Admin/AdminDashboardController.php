<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pricing;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminDashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats()
    {
        // Total users
        $totalUsers = User::count();
        
        // Active users (subscription active)
        $activeUsers = User::where('status', 'active')
            ->where('subscription_status', 'active')
            ->where(function ($query) {
                $query->whereNull('subscription_expires_at')
                    ->orWhere('subscription_expires_at', '>=', now());
            })
            ->count();

        // Users by plan
        $usersByPlan = User::select('plan', DB::raw('count(*) as count'))
            ->where('status', 'active')
            ->groupBy('plan')
            ->pluck('count', 'plan')
            ->toArray();

        // Get pricing
        $pricings = Pricing::where('is_active', true)->get()->keyBy('plan');

        // Calculate revenue
        $monthlyRevenue = 0;
        $totalRevenue = 0;

        foreach ($usersByPlan as $plan => $count) {
            $price = $pricings->get($plan)?->price ?? 0;
            if ($plan !== 'free' && $price > 0) {
                $monthlyRevenue += $count * $price;
            }
        }

        // For total revenue, we calculate based on active subscriptions
        // This is a simplified calculation - in production you might want to track actual payments
        $totalRevenue = $monthlyRevenue; // Same as monthly for now

        // Recent users (last 30 days)
        $recentUsers = User::where('created_at', '>=', now()->subDays(30))->count();

        // Users expiring soon (next 7 days)
        $expiringSoon = User::where('subscription_status', 'active')
            ->whereBetween('subscription_expires_at', [now(), now()->addDays(7)])
            ->count();

        // New users (last 7 days)
        $newUsersLast7Days = User::where('created_at', '>=', now()->subDays(7))->count();

        // Analytics: User growth per week (last 4 weeks)
        $userGrowthData = [];
        $weeks = 4;
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            // Shorter label format: "Week of M d" or "M d - M d" if different months
            if ($weekStart->format('M') === $weekEnd->format('M')) {
                $weekLabel = 'Week ' . ($weeks - $i) . ': ' . $weekStart->format('M d') . '-' . $weekEnd->format('d');
            } else {
                $weekLabel = 'Week ' . ($weeks - $i) . ': ' . $weekStart->format('M d') . ' - ' . $weekEnd->format('M d');
            }
            
            $count = User::whereBetween('created_at', [$weekStart, $weekEnd])->count();
            $userGrowthData[] = [
                'date' => $weekStart->format('Y-m-d'),
                'count' => $count,
                'label' => $weekLabel,
                'week' => $weekStart->format('W'),
            ];
        }

        // Analytics: Revenue trend per week (last 4 weeks)
        $revenueTrendData = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            // Shorter label format
            if ($weekStart->format('M') === $weekEnd->format('M')) {
                $weekLabel = 'Week ' . ($weeks - $i) . ': ' . $weekStart->format('M d') . '-' . $weekEnd->format('d');
            } else {
                $weekLabel = 'Week ' . ($weeks - $i) . ': ' . $weekStart->format('M d') . ' - ' . $weekEnd->format('M d');
            }
            
            // Get users active at the end of the week
            $usersAtWeekEnd = User::where('status', 'active')
                ->where('subscription_status', 'active')
                ->where('created_at', '<=', $weekEnd->endOfDay())
                ->where(function ($query) use ($weekEnd) {
                    $query->whereNull('subscription_expires_at')
                        ->orWhere('subscription_expires_at', '>=', $weekEnd);
                })
                ->get();
            
            $weeklyRevenue = 0;
            foreach ($usersAtWeekEnd as $user) {
                $price = $pricings->get($user->plan)?->price ?? 0;
                if ($user->plan !== 'free' && $price > 0) {
                    $weeklyRevenue += $price;
                }
            }
            
            $revenueTrendData[] = [
                'date' => $weekStart->format('Y-m-d'),
                'revenue' => $weeklyRevenue,
                'label' => $weekLabel,
                'week' => $weekStart->format('W'),
            ];
        }

        // Analytics: Users by plan distribution
        $planDistribution = [
            ['name' => 'Free', 'value' => $usersByPlan['free'] ?? 0, 'color' => '#64748b'],
            ['name' => 'Pro', 'value' => $usersByPlan['pro'] ?? 0, 'color' => '#3b82f6'],
            ['name' => 'VIP', 'value' => $usersByPlan['vip'] ?? 0, 'color' => '#8b5cf6'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'recent' => $recentUsers,
                    'new_last_7_days' => $newUsersLast7Days,
                    'expiring_soon' => $expiringSoon,
                ],
                'revenue' => [
                    'monthly' => $monthlyRevenue,
                    'total' => $totalRevenue,
                    'formatted_monthly' => 'Rp ' . number_format($monthlyRevenue, 0, ',', '.'),
                    'formatted_total' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                ],
                'users_by_plan' => [
                    'free' => $usersByPlan['free'] ?? 0,
                    'pro' => $usersByPlan['pro'] ?? 0,
                    'vip' => $usersByPlan['vip'] ?? 0,
                ],
                'analytics' => [
                    'user_growth' => $userGrowthData,
                    'revenue_trend' => $revenueTrendData,
                    'plan_distribution' => $planDistribution,
                ],
            ],
        ]);
    }

    /**
     * Get users list with pagination
     */
    public function users(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100); // Limit max per page
        $search = $request->input('search');
        $plan = $request->input('plan');
        $status = $request->input('status');

        $query = User::query();

        // Secure search with parameterized queries (Laravel's where already does this)
        if ($search) {
            $search = trim($search);
            // Additional validation: only allow alphanumeric, spaces, and common phone chars
            if (preg_match('/^[a-zA-Z0-9\s\-\+\(\)]+$/', $search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('phone_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            }
        }

        // Validate plan input
        if ($plan && in_array($plan, ['free', 'pro', 'vip'])) {
            $query->where('plan', $plan);
        }

        // Validate status input
        if ($status && in_array($status, ['active', 'blocked', 'inactive'])) {
            $query->where('status', $status);
        }

        // Optimize: Only select needed columns and eager load if needed
        $users = $query->select([
            'id', 'name', 'phone_number', 'plan', 'status', 
            'subscription_status', 'subscription_expires_at', 'response_style', 'created_at'
        ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Update user plan
     */
    public function updateUserPlan(Request $request, $id)
    {
        $request->validate([
            'plan' => 'required|in:free,pro,vip',
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $oldPlan = $user->plan;
        $newPlan = $request->plan;

        // Update plan
        $user->plan = $newPlan;
        
        // If plan changed, update subscription expiry date accordingly
        if ($oldPlan !== $newPlan) {
            $user->initializeSubscription($newPlan);
        } else {
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'User plan updated successfully',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Update user chat style (response_style)
     */
    public function updateChatStyle(Request $request, $id)
    {
        $request->validate([
            'response_style' => 'required|in:santai,netral,formal,gaul',
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->response_style = $request->response_style;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Chat style updated successfully',
            'data' => $user->fresh(),
        ]);
    }


    /**
     * Get user details
     */
    public function user($id)
    {
        $user = User::with('transactions')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Delete user (this will cascade delete related records if foreign keys are set up)
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus',
        ]);
    }

    /**
     * Get financial data (income and expense) for all users
     */
    public function financialData(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $search = $request->input('search');

        $query = User::select([
            'users.id',
            'users.name',
            'users.phone_number',
            'users.plan',
            'users.status',
            'users.created_at'
        ])
        ->withCount([
            'transactions as total_income' => function ($q) {
                $q->where('type', 'income');
            },
            'transactions as total_expense' => function ($q) {
                $q->where('type', 'expense');
            }
        ])
        ->addSelect([
            'income_sum' => Transaction::selectRaw('COALESCE(SUM(amount), 0)')
                ->whereColumn('user_id', 'users.id')
                ->where('type', 'income'),
            'expense_sum' => Transaction::selectRaw('COALESCE(SUM(amount), 0)')
                ->whereColumn('user_id', 'users.id')
                ->where('type', 'expense'),
        ]);

        // Search filter
        if ($search) {
            $search = trim($search);
            if (preg_match('/^[a-zA-Z0-9\s\-\+\(\)]+$/', $search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('users.phone_number', 'like', "%{$search}%")
                        ->orWhere('users.name', 'like', "%{$search}%");
                });
            }
        }

        // Get users with pagination
        $users = $query->orderBy('users.created_at', 'desc')
            ->paginate($perPage);

        // Format the response with financial data
        $formattedUsers = $users->getCollection()->map(function ($user) {
            $incomeSum = (int) ($user->income_sum ?? 0);
            $expenseSum = (int) ($user->expense_sum ?? 0);
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
                'plan' => $user->plan,
                'status' => $user->status,
                'created_at' => $user->created_at,
                'total_income' => $incomeSum,
                'total_expense' => $expenseSum,
                'total_income_count' => $user->total_income ?? 0,
                'total_expense_count' => $user->total_expense ?? 0,
                'formatted_income' => 'Rp ' . number_format($incomeSum, 0, ',', '.'),
                'formatted_expense' => 'Rp ' . number_format($expenseSum, 0, ',', '.'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $formattedUsers,
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Get expense details for a specific user with month filter
     */
    public function getUserExpenses(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $month = $request->input('month'); // Format: YYYY-MM
        
        $query = Transaction::where('user_id', $id)
            ->where('type', 'expense');

        if ($month) {
            // Validate month format
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
                $query->whereBetween('tanggal', [$startDate, $endDate]);
            }
        }

        $expenses = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $expenses->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $expenses,
                'total' => $total,
                'month' => $month,
            ],
        ]);
    }

    /**
     * Get income details for a specific user with month filter
     */
    public function getUserIncomes(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $month = $request->input('month'); // Format: YYYY-MM
        
        $query = Transaction::where('user_id', $id)
            ->where('type', 'income');

        if ($month) {
            // Validate month format
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
                $query->whereBetween('tanggal', [$startDate, $endDate]);
            }
        }

        $incomes = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $incomes->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $incomes,
                'total' => $total,
                'month' => $month,
            ],
        ]);
    }

    /**
     * Generate PDF for user expenses
     */
    public function generateExpensePdf(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $month = $request->input('month'); // Format: YYYY-MM
        
        $query = Transaction::where('user_id', $id)
            ->where('type', 'expense');

        if ($month) {
            // Validate month format
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
                $query->whereBetween('tanggal', [$startDate, $endDate]);
            }
        }

        $expenses = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $expenses->sum('amount');

        // Format month for display
        $monthDisplay = $month 
            ? Carbon::createFromFormat('Y-m', $month)->format('F Y')
            : 'Semua Waktu';

        // Generate PDF using dompdf
        try {
            $pdf = Pdf::loadView('reports.expense-detail', [
                'user' => $user,
                'expenses' => $expenses,
                'total' => $total,
                'month' => $monthDisplay,
            ]);

            $filename = 'pengeluaran_' . str_replace(' ', '_', $user->name) . '_' . ($month ?: 'all') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate PDF for user incomes
     */
    public function generateIncomePdf(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $month = $request->input('month'); // Format: YYYY-MM
        
        $query = Transaction::where('user_id', $id)
            ->where('type', 'income');

        if ($month) {
            // Validate month format
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
                $query->whereBetween('tanggal', [$startDate, $endDate]);
            }
        }

        $incomes = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $incomes->sum('amount');

        // Format month for display
        $monthDisplay = $month 
            ? Carbon::createFromFormat('Y-m', $month)->format('F Y')
            : 'Semua Waktu';

        // Generate PDF using dompdf
        try {
            $pdf = Pdf::loadView('reports.income-detail', [
                'user' => $user,
                'incomes' => $incomes,
                'total' => $total,
                'month' => $monthDisplay,
            ]);

            $filename = 'pemasukan_' . str_replace(' ', '_', $user->name) . '_' . ($month ?: 'all') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}

