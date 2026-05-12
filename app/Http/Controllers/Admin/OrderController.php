<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SystemConfigService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected SystemConfigService $config) {}

    // ── Index ──────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        // ── Lightweight poll for JS auto-refresh ──────────────────────────────
        if ($request->boolean('_poll')) {
            return response()->json([
                'latest_id'     => Order::max('id') ?? 0,
                'pending_count' => Order::where('status', 'pending')->count(),
            ]);
        }

        $query = Order::with('customer')
            ->orderByDesc('id');

        // Filter by status tab
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Search by order_number or customer phone
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', fn($cq) =>
                      $cq->where('phone', 'like', '%' . $search . '%')
                         ->orWhere('full_name', 'like', '%' . $search . '%')
                  );
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        // Counts per status for tab badges
        $counts = Order::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Latest order ID for JS polling baseline
        $latestId = Order::max('id') ?? 0;

        return view('admin.orders.index', compact('orders', 'counts', 'latestId'));
    }

    // ── Show ───────────────────────────────────────────────────────────────────
    public function show(Order $order)
    {
        $order->load('customer', 'items');
        $rejectionReasons = $this->config->getRejectionReasons();

        return view('admin.orders.show', compact('order', 'rejectionReasons'));
    }

    // ── Accept ─────────────────────────────────────────────────────────────────
    public function accept(Request $request, Order $order)
    {
        if ($order->status !== Order::STATUS_PENDING) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $request->validate([
            'delivery_fee' => 'nullable|numeric|min:0',
        ]);

        $deliveryFee = (float) ($request->input('delivery_fee') ?? 0);
        $total       = (float) $order->subtotal + $deliveryFee;

        $order->update([
            'status'       => Order::STATUS_ACCEPTED,
            'delivery_fee' => $deliveryFee > 0 ? $deliveryFee : null,
            'total'        => $total,
            'accepted_at'  => now(),
        ]);

        return back()->with('success', __('app.order_accepted'));
    }

    // ── Reject ─────────────────────────────────────────────────────────────────
    public function reject(Request $request, Order $order)
    {
        if ($order->status !== Order::STATUS_PENDING) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $order->update([
            'status'           => Order::STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return back()->with('success', __('app.order_rejected'));
    }

    // ── Cancel (admin) ─────────────────────────────────────────────────────────
    public function cancel(Request $request, Order $order)
    {
        $terminal = [
            Order::STATUS_COMPLETED,
            Order::STATUS_REJECTED,
            Order::STATUS_CANCELLED,
            Order::STATUS_CANCELLED_BY_ADMIN,
            Order::STATUS_CANCELLED_BY_CUSTOMER,
        ];

        if (in_array($order->status, $terminal)) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $order->update([
            'status'       => Order::STATUS_CANCELLED_BY_ADMIN,
            'cancelled_at' => now(),
        ]);

        return back()->with('success', __('app.order_cancelled'));
    }

    // ── Complete ───────────────────────────────────────────────────────────────
    public function complete(Request $request, Order $order)
    {
        if ($order->status !== Order::STATUS_ACCEPTED) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $order->update([
            'status'       => Order::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return back()->with('success', __('app.order_completed'));
    }

    // ── Invoice ────────────────────────────────────────────────────────────────
    public function invoice(Order $order)
    {
        $order->load('customer', 'items');

        $restaurantName  = $this->config->get('restaurant_name', config('app.name'));
        $restaurantPhone = $this->config->get('restaurant_phone');
        $restaurantLogo  = $this->config->get('restaurant_logo');

        return view('admin.orders.invoice', compact(
            'order',
            'restaurantName',
            'restaurantPhone',
            'restaurantLogo'
        ));
    }
}

