<?php

namespace App\Http\Controllers\API;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{
    public function dashboard(Request $request)
    {
        $today = Carbon::today();

        // ── Summary cards ───────────────────────────────────────────────────
        $ordersToday   = Order::whereDate('created_at', $today)->count();
        $salesToday    = Order::whereDate('created_at', $today)
                            ->whereNotIn('status', [
                                Order::STATUS_CANCELLED,
                                Order::STATUS_CANCELLED_BY_ADMIN,
                                Order::STATUS_CANCELLED_BY_CUSTOMER,
                                Order::STATUS_REJECTED,
                            ])->sum('total');
        $totalCustomers = Customer::count();
        $pendingOrders  = Order::where('status', Order::STATUS_PENDING)->count();

        // ── Orders per day for the last 7 days (for chart) ──────────────────
        $weekLabels = [];
        $weekCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $day          = Carbon::today()->subDays($i);
            $weekLabels[] = $day->translatedFormat('D d/m');
            $weekCounts[] = Order::whereDate('created_at', $day)->count();
        }

        // ── Recent orders ────────────────────────────────────────────────────
        $recentOrders = Order::with('customer')
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'ordersToday',
            'salesToday',
            'totalCustomers',
            'pendingOrders',
            'weekLabels',
            'weekCounts',
            'recentOrders',
        ));
    }

    public function welcome(Request $request)
    {
        if (auth()->user())
            return redirect()->route('admin.dashboard');
        else
            return view('auth.login');
    }
}
