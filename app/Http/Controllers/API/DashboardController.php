<?php

namespace App\Http\Controllers\API;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\SystemConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends BaseController
{
    public function __construct(
        protected SystemConfigService $config,
    ) {}

    public function dashboard(Request $request)
    {
        // ── Summary cards (cached 120s; flushed by Order model events) ────────
        $stats = Cache::remember(Order::DASHBOARD_STATS_CACHE_KEY, 120, function () {
            $today = Carbon::today();

            return [
                'ordersToday' => Order::whereDate('created_at', $today)->count(),
                'salesToday' => Order::whereDate('created_at', $today)
                    ->whereNotIn('status', [
                        Order::STATUS_CANCELLED_BY_CUSTOMER,
                        Order::STATUS_REJECTED,
                        // Superseded by a replacement order — exclude to avoid double-counting.
                        Order::STATUS_MODIFIED,
                    ])->sum('total'),
                'pendingOrders' => Order::where('status', Order::STATUS_PENDING)->count(),
            ];
        });

        $ordersToday = $stats['ordersToday'];
        $salesToday = $stats['salesToday'];
        $pendingOrders = $stats['pendingOrders'];

        // ── Recent orders (kept live — cheap latest-10, should always be fresh) ─
        $recentOrders = Order::latest()
            ->limit(10)
            ->get();

        // ── Heavier aggregates: cached 300s, flushed by Order model events ─────
        $detailed = Cache::remember(Order::DASHBOARD_DETAILED_STATS_CACHE_KEY, 300, function () {
            $excludedStatuses = [
                Order::STATUS_CANCELLED_BY_CUSTOMER,
                Order::STATUS_REJECTED,
                Order::STATUS_MODIFIED,
            ];

            // Order-type breakdown — one grouped query (was 3 count()s in the view).
            $typeCounts = Order::selectRaw('order_type, COUNT(*) as cnt')
                ->groupBy('order_type')
                ->pluck('cnt', 'order_type');

            // Weekly chart — one grouped query (was 7 separate per-day counts).
            $weekStart = Carbon::today()->subDays(6)->startOfDay();
            $daily = Order::where('created_at', '>=', $weekStart)
                ->selectRaw('DATE(created_at) as d, COUNT(*) as cnt')
                ->groupBy('d')
                ->pluck('cnt', 'd');

            $weekLabels = [];
            $weekCounts = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = Carbon::today()->subDays($i);
                $weekLabels[] = $day->format('D');
                $weekCounts[] = (int) ($daily[$day->format('Y-m-d')] ?? 0);
            }

            return [
                'totalCustomers' => Customer::count(),
                'topCustomers' => Customer::withCount('orders')
                    ->orderByDesc('orders_count')->limit(5)->get(),
                'topProducts' => OrderItem::query()
                    ->selectRaw('product_name, SUM(quantity) as total_sold')
                    ->whereHas('order', fn ($q) => $q->whereNotIn('status', $excludedStatuses))
                    ->groupBy('product_name')->orderByDesc('total_sold')->limit(5)->get(),
                'weekLabels' => $weekLabels,
                'weekCounts' => $weekCounts,
                'typeTable' => (int) ($typeCounts[Order::TYPE_TABLE] ?? 0),
                'typeDelivery' => (int) ($typeCounts[Order::TYPE_DELIVERY] ?? 0),
                'typeTakeaway' => (int) ($typeCounts[Order::TYPE_TAKEAWAY] ?? 0),
            ];
        });

        [
            'totalCustomers' => $totalCustomers,
            'topCustomers' => $topCustomers,
            'topProducts' => $topProducts,
            'weekLabels' => $weekLabels,
            'weekCounts' => $weekCounts,
            'typeTable' => $typeTable,
            'typeDelivery' => $typeDelivery,
            'typeTakeaway' => $typeTakeaway,
        ] = $detailed;

        // ── Restaurant branding (shown in the dashboard banner) ───────────────
        $restaurantName = $this->config->getFirstText(
            ['restaurant_name_ar', 'restaurant_name', 'restaurant_name_en', 'site_name'],
            config('app.name', '')
        );
        $restaurantNameAr = $this->config->getFirstText(['restaurant_name_ar', 'restaurant_name'], '');
        $restaurantNameEn = $this->config->getFirstText(['restaurant_name_en'], '');
        $restaurantLogo = $this->config->get('restaurant_logo');

        return view('dashboard', compact(
            'ordersToday',
            'salesToday',
            'pendingOrders',
            'recentOrders',
            'totalCustomers',
            'topCustomers',
            'topProducts',
            'weekLabels',
            'weekCounts',
            'typeTable',
            'typeDelivery',
            'typeTakeaway',
            'restaurantName',
            'restaurantNameAr',
            'restaurantNameEn',
            'restaurantLogo',
        ));
    }

    public function welcome(Request $request)
    {
        if (auth()->user()) {
            return redirect()->route('admin.dashboard');
        } else {
            return view('auth.login');
        }
    }
}
