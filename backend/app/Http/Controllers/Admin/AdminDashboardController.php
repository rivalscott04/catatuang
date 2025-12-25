<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
            'subscription_status', 'subscription_expires_at', 'created_at'
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

        $user->plan = $request->plan;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User plan updated successfully',
            'data' => $user,
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
}

