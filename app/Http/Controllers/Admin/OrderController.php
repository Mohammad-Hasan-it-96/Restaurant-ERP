<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Services\NotificationService;
use App\Services\SystemConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected SystemConfigService $config,
        protected NotificationService $notifications,
    ) {}

    // ── Index ──────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        // ── Lightweight poll for JS auto-refresh ──────────────────────────────
        if ($request->boolean('_poll')) {
            return response()->json([
                'latest_id' => Order::max('id') ?? 0,
                'pending_count' => Order::query()->where('status', 'pending')->count(),
            ]);
        }

        $allowed = ['id', 'total', 'status', 'created_at'];
        $sortBy = in_array($request->input('sort'), $allowed) ? $request->input('sort') : 'id';
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
                $q->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($cq) => $cq->where('phone', 'like', '%'.$search.'%')
                        ->orWhere('full_name', 'like', '%'.$search.'%')
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
        $rejectionReasons = $this->config->getRejectionReasons();
        $restaurantName = $this->config->getFirstText(
            ['restaurant_name_ar', 'restaurant_name', 'restaurant_name_en', 'site_name'],
            config('app.name', '')
        );
        // Only load the WhatsApp number when the order-view feature is on —
        // the Blade panel is also @feature-wrapped, this avoids loading unused data.
        $restaurantWhatsapp = feature('notifications.whatsapp_in_order_view')
            ? $this->config->getText('restaurant_whatsapp')
            : '';
        $deliveryZones = DeliveryZone::active()->get();

        $back = $request->headers->get('referer', route('admin.orders.index'));

        return view('admin.orders.show', compact(
            'order', 'rejectionReasons', 'restaurantName', 'restaurantWhatsapp', 'deliveryZones', 'back'
        ));
    }

    // ── Accept ─────────────────────────────────────────────────────────────────
    public function accept(Request $request, Order $order)
    {
        if (! $order->canTransitionTo(Order::STATUS_ACCEPTED)) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        // Delivery orders must carry a valid fee (> 0); dine-in / takeaway have none.
        $deliveryFee = null;
        if ($order->order_type === Order::TYPE_DELIVERY) {
            $deliveryFee = $this->validDeliveryFee($request);
            if ($deliveryFee === null) {
                return back()->with('error', __('app.delivery_fee_invalid'));
            }
        }

        $order->update([
            'status' => Order::STATUS_ACCEPTED,
            'delivery_fee' => $deliveryFee,
            'total' => (float) $order->subtotal + (float) ($deliveryFee ?? 0),
            'accepted_at' => now(),
        ]);

        $order->load('customer');
        $this->notifications->notifyOrderStatus($order);

        activity()->log('order.accepted', $order, 'Order #'.$order->order_number, [
            'to' => Order::STATUS_ACCEPTED,
            'delivery_fee' => $deliveryFee,
        ]);

        return back()->with('success', __('app.order_accepted'));
    }

    // ── Edit Delivery Fee (delivery orders, while still accepted) ──────────────
    public function setDeliveryFee(Request $request, Order $order)
    {
        // Only delivery orders carry a delivery fee.
        if ($order->order_type !== Order::TYPE_DELIVERY) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        // Editable only while still accepted — locked once it moves to ready/delivered.
        if ($order->status !== Order::STATUS_ACCEPTED) {
            return back()->with('error', __('app.delivery_fee_edit_not_allowed'));
        }

        $newFee = $this->validDeliveryFee($request);
        if ($newFee === null) {
            return back()->with('error', __('app.delivery_fee_invalid'));
        }

        $oldFee = $order->delivery_fee;

        // Update the fee (whether or not one was already set) and recompute the total.
        $order->update([
            'delivery_fee' => $newFee,
            'total' => (float) $order->subtotal + $newFee,
        ]);

        // user_id (the acting admin) is auto-attached via InjectLogContext.
        logService()->info('order.delivery_fee.updated', [
            'order_id' => $order->id,
            'old_fee' => $oldFee,
            'new_fee' => $newFee,
        ]);

        return back()->with('success', __('app.delivery_fee_updated'));
    }

    /**
     * Validate a submitted delivery_fee: required, numeric, and >= 1.
     * Returns the float value, or null if invalid (caller surfaces the Arabic
     * message). Done manually rather than via $request->validate() because this
     * admin UI renders no $errors bag — only session flash toasts are shown.
     */
    private function validDeliveryFee(Request $request): ?float
    {
        $fee = $request->input('delivery_fee');

        if ($fee === null || $fee === '' || ! is_numeric($fee) || (float) $fee < 1) {
            return null;
        }

        return (float) $fee;
    }

    // ── Reject ─────────────────────────────────────────────────────────────────
    public function reject(Request $request, Order $order)
    {
        feature_or_fail('orders.admin_cancel');

        if (! $order->canTransitionTo(Order::STATUS_REJECTED)) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $order->update([
            'status' => Order::STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        $order->load('customer');
        $this->notifications->notifyOrderStatus($order);

        activity()->log('order.rejected', $order, 'Order #'.$order->order_number, [
            'reason' => $order->rejection_reason,
        ]);

        return back()->with('success', __('app.order_rejected'));
    }

    // ── Mark Ready (accepted → ready) ─────────────────────────────────────────
    public function markReady(Order $order)
    {
        if (! $order->canTransitionTo(Order::STATUS_READY)) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $order->update(['status' => Order::STATUS_READY]);

        $order->load('customer');
        $this->notifications->notifyOrderStatus($order);

        activity()->log('order.ready', $order, 'Order #'.$order->order_number);

        return back()->with('success', __('app.order_marked_ready'));
    }

    // ── Mark Delivered (ready → delivered) ────────────────────────────────────
    public function markDelivered(Order $order)
    {
        if (! $order->canTransitionTo(Order::STATUS_DELIVERED)) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        $order->update(['status' => Order::STATUS_DELIVERED]);

        activity()->log('order.delivered', $order, 'Order #'.$order->order_number);

        return back()->with('success', __('app.order_marked_delivered'));
    }

    // ── Mark Completed (delivered → completed) ────────────────────────────────
    public function markCompleted(Order $order)
    {
        if (! $order->canTransitionTo(Order::STATUS_COMPLETED)) {
            return back()->with('error', __('app.order_action_not_allowed'));
        }

        // Payment must be recorded before an order can be completed.
        if (! $order->is_paid) {
            return back()->with('error', __('app.payment_required_before_completion'));
        }

        $order->update([
            'status' => Order::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        activity()->log('order.completed', $order, 'Order #'.$order->order_number);

        return back()->with('success', __('app.order_completed'));
    }

    // ── Mark As Paid (one-click cash payment) ─────────────────────────────────
    public function markAsPaid(Order $order)
    {
        // Prevent duplicate payment.
        if ($order->is_paid) {
            return back()->with('info', __('app.already_paid'));
        }

        $order->update([
            'is_paid' => true,
            'paid_at' => now(),
            // Keep the legacy fields in sync (read by dashboard / reports / SPA).
            'payment_status' => Order::PAYMENT_PAID,
            'payment_method' => 'cash',
        ]);

        activity()->log('order.paid', $order, 'Order #'.$order->order_number, [
            'method' => 'cash',
        ]);

        return back()->with('success', __('app.order_marked_paid'));
    }

    // ── Invoice ────────────────────────────────────────────────────────────────
    public function invoice(Order $order)
    {
        $order->load('customer', 'items');

        $restaurantName = $this->config->getFirstText(
            ['restaurant_name_ar', 'restaurant_name', 'restaurant_name_en', 'site_name'],
            config('app.name', '')
        );
        $restaurantMobiles = array_values(array_filter([
            $this->config->getText('restaurant_phone'),
            $this->config->getText('restaurant_mobile_2'),
        ]));
        $restaurantLogo = $this->config->get('restaurant_logo');

        return view('admin.orders.invoice', compact(
            'order',
            'restaurantName',
            'restaurantMobiles',
            'restaurantLogo'
        ));
    }

    // ── Test Push Notification ─────────────────────────────────────────────────
    public function testNotification(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required_without:customer_id|string|max:255|nullable',
            'customer_id' => 'required_without:fcm_token|integer|nullable',
            'status' => 'in:accepted,rejected,ready',
        ]);

        $fcmToken = $request->input('fcm_token');

        if (! $fcmToken && $customerId = $request->input('customer_id')) {
            $fcmToken = Customer::find($customerId)?->fcm_token;
            if (! $fcmToken) {
                return response()->json(['success' => false, 'message' => 'Customer has no FCM token registered.'], 422);
            }
        }

        $status = $request->input('status', 'accepted');
        $titles = [
            'accepted' => '✅ تم قبول طلبك',
            'rejected' => '❌ تم رفض طلبك',
            'ready' => '🎉 طلبك جاهز!',
        ];
        $bodies = [
            'accepted' => 'يتم الآن تحضير طلبك — هذا إشعار تجريبي',
            'rejected' => 'طلبك رقم TEST-0000 — هذا إشعار تجريبي',
            'ready' => 'يمكنك الآن استلام طلبك — هذا إشعار تجريبي',
        ];

        try {
            $this->notifications->sendFcm(
                $fcmToken,
                $titles[$status],
                $bodies[$status],
                ['status' => $status, 'test' => 'true'],
            );

            return response()->json(['success' => true, 'message' => 'Notification sent successfully.', 'fcm_token' => $fcmToken]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
