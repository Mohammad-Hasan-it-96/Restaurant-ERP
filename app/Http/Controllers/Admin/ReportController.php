<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ─── Shared helpers ───────────────────────────────────────────────────────

    /**
     * Parse and return validated filter values from the request.
     */
    private function resolveFilters(Request $request): array
    {
        $dateFrom = Carbon::parse(
            $request->input('date_from', Carbon::now()->startOfMonth()->toDateString())
        )->startOfDay();

        $dateTo = Carbon::parse(
            $request->input('date_to', Carbon::today()->toDateString())
        )->endOfDay();

        $status = $request->input('status', 'completed');

        return compact('dateFrom', 'dateTo', 'status');
    }

    /**
     * Apply date range + optional status filter to an Eloquent Order query.
     */
    private function applyFilters($query, array $f): mixed
    {
        $query->whereBetween('created_at', [$f['dateFrom'], $f['dateTo']]);

        if ($f['status'] !== 'all') {
            $query->where('status', $f['status']);
        }

        return $query;
    }

    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);

        // ── Summary cards ────────────────────────────────────────────────────
        $baseSummary = $this->applyFilters(Order::query(), $filters);

        $totalRevenue = (float) (clone $baseSummary)->sum('total');
        $totalOrders = (clone $baseSummary)->count();
        $averageOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0;
        $totalDeliveryFees = (float) (clone $baseSummary)->sum('delivery_fee');

        // ── Revenue by day (for bar chart) ───────────────────────────────────
        $revenueByDay = (clone $baseSummary)
            ->selectRaw('DATE(created_at) as date, ROUND(SUM(total), 2) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'revenue' => (float) $r->revenue]);

        // ── Orders by type (for doughnut) ────────────────────────────────────
        $ordersByType = (clone $baseSummary)
            ->selectRaw('order_type, COUNT(*) as cnt')
            ->groupBy('order_type')
            ->pluck('cnt', 'order_type');

        // ── Orders by status — always across ALL statuses for chosen date range
        $ordersByStatus = Order::whereBetween('created_at', [$filters['dateFrom'], $filters['dateTo']])
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        // ── Top 10 products ──────────────────────────────────────────────────
        $topQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$filters['dateFrom'], $filters['dateTo']]);

        if ($filters['status'] !== 'all') {
            $topQuery->where('orders.status', $filters['status']);
        }

        $topProducts = $topQuery
            ->selectRaw('
                order_items.product_name,
                SUM(order_items.quantity)    AS total_quantity,
                ROUND(SUM(order_items.total), 2) AS total_revenue
            ')
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // ── Paginated orders table ────────────────────────────────────────────
        $orders = $this->applyFilters(Order::with('customer'), $filters)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.index', compact(
            'filters',
            'totalRevenue',
            'totalOrders',
            'averageOrderValue',
            'totalDeliveryFees',
            'revenueByDay',
            'ordersByType',
            'ordersByStatus',
            'topProducts',
            'orders'
        ));
    }

    // ─── CSV Export ───────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        $filters = $this->resolveFilters($request);

        $orders = $this->applyFilters(Order::with('customer'), $filters)
            ->orderByDesc('created_at')
            ->get();

        $filename = 'orders-report-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache',
            'Pragma' => 'no-cache',
        ];

        $callback = function () use ($orders) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens Arabic/special chars correctly
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Order Number',
                'Customer Name',
                'Customer Phone',
                'Order Type',
                'Status',
                'Subtotal',
                'Delivery Fee',
                'Total',
                'Created At',
            ]);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->customer->full_name ?? '',
                    $order->customer->phone ?? '',
                    $order->order_type,
                    $order->status,
                    number_format((float) $order->subtotal, 2, '.', ''),
                    number_format((float) ($order->delivery_fee ?? 0), 2, '.', ''),
                    number_format((float) $order->total, 2, '.', ''),
                    $order->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
