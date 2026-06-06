<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
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
                'pending_count' => Order::query()->where('status', 'pending')->count(),
            ]);
        }

        $allowed   = ['id', 'total', 'status', 'created_at'];
        $sortBy    = in_array($request->input('sort'), $allowed) ? $request->input('sort') : 'id';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $query = Order::with('customer')
            ->orderBy($sortBy, $direction);

        // Filter by status tab
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Search by order_number, customer_name, phone (snapshot columns) or customer relation
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhere('customer_name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
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
    public function show(Request $request, Order $order)
    {
        $order->load('customer', 'items');
        $rejectionReasons   = $this->config->getRejectionReasons();
        $restaurantName  = $this->config->getFirstText(
            ['restaurant_name_ar', 'restaurant_name', 'restaurant_name_en', 'site_name'],
            config('app.name', '')
        );
        $restaurantWhatsapp = $this->config->getText('restaurant_whatsapp');
        $deliveryZones      = DeliveryZone::active()->get();

        $back = $request->headers->get('referer', route('admin.orders.index'));

        return view('admin.orders.show', compact(
            'order', 'rejectionReasons', 'restaurantName', 'restaurantWhatsapp', 'deliveryZones', 'back'
        ));
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

    // ── Complete (legacy POST route — still valid from accepted state) ────────
    public function complete(Request $request, Order $order)
    {
        $allowed = [Order::STATUS_ACCEPTED, Order::STATUS_READY, Order::STATUS_DELIVERED];
        if (! in_array($order->status, $allowed)) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $order->update([
            'status'       => Order::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return back()->with('success', __('app.order_completed'));
    }

    // ── Mark Preparing (accepted → preparing) ─────────────────────────────────
    public function markPreparing(Order $order)
    {
        if ($order->status !== Order::STATUS_ACCEPTED) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $order->update(['status' => Order::STATUS_PREPARING]);

        return back()->with('success', __('app.order_marked_preparing'));
    }

    // ── Mark Ready (preparing → ready) ────────────────────────────────────────
    public function markReady(Order $order)
    {
        if ($order->status !== Order::STATUS_PREPARING) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $order->update(['status' => Order::STATUS_READY]);

        return back()->with('success', __('app.order_marked_ready'));
    }

    // ── Mark Delivered (ready → delivered) — delivery orders only ─────────────
    public function markDelivered(Order $order)
    {
        if ($order->status !== Order::STATUS_READY) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        if ($order->order_type !== Order::TYPE_DELIVERY) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $order->update(['status' => Order::STATUS_DELIVERED]);

        return back()->with('success', __('app.order_marked_delivered'));
    }

    // ── Mark Completed (ready for table/takeaway OR delivered for delivery) ────
    public function markCompleted(Order $order)
    {
        $allowed = [Order::STATUS_READY, Order::STATUS_DELIVERED];
        if (! in_array($order->status, $allowed)) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        // Delivery orders must go through delivered first (unless admin forces it)
        if ($order->order_type === Order::TYPE_DELIVERY
            && $order->status === Order::STATUS_READY) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $order->update([
            'status'       => Order::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return back()->with('success', __('app.order_completed'));
    }

    // ── Mark Paid ─────────────────────────────────────────────────────────────
    public function markPaid(Order $order)
    {
        if ($order->payment_status === Order::PAYMENT_PAID) {
            return back()->with('info', __('app.already_paid'));
        }

        $order->update([
            'payment_status' => Order::PAYMENT_PAID,
            'payment_method' => 'cash',
        ]);

        return back()->with('success', __('app.order_marked_paid'));
    }

    // ── Invoice ────────────────────────────────────────────────────────────────
    public function invoice(Order $order)
    {
        $order->load('customer', 'items');

        $restaurantName  = $this->config->getFirstText(
            ['restaurant_name_ar', 'restaurant_name', 'restaurant_name_en', 'site_name'],
            config('app.name', '')
        );
        $restaurantPhone = $this->config->getText('restaurant_phone');
        $restaurantLogo  = $this->config->get('restaurant_logo');

        return view('admin.orders.invoice', compact(
            'order',
            'restaurantName',
            'restaurantPhone',
            'restaurantLogo'
        ));
    }
}

