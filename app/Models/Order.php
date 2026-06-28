<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Order extends Model
{
    /** Cache key for the admin dashboard summary stat cards. */
    const DASHBOARD_STATS_CACHE_KEY = 'dashboard.stats';

    /** Cache key for the heavier dashboard aggregates (charts, top lists). */
    const DASHBOARD_DETAILED_STATS_CACHE_KEY = 'dashboard.detailed_stats';

    /** Cache key for the order index status badge counts. */
    const STATUS_COUNTS_CACHE_KEY = 'orders.status_counts';

    /**
     * Flush the cached dashboard stats + order status counts whenever an order is
     * created, updated (e.g. a status change), or deleted. Every write path goes
     * through Eloquent (Order::create / $order->update), so the saved/deleted
     * events cover them all in one place.
     */
    protected static function booted(): void
    {
        $flush = function () {
            Cache::forget(self::DASHBOARD_STATS_CACHE_KEY);
            Cache::forget(self::DASHBOARD_DETAILED_STATS_CACHE_KEY);
            Cache::forget(self::STATUS_COUNTS_CACHE_KEY);
        };

        static::saved($flush);
        static::deleted($flush);
    }

    // ??? Order type constants ??????????????????????????????????????
    const TYPE_TABLE = 'table';

    const TYPE_DELIVERY = 'delivery';

    const TYPE_TAKEAWAY = 'takeaway';

    // ??? Status constants ??????????????????????????????????????????
    const STATUS_PENDING = 'pending';

    const STATUS_ACCEPTED = 'accepted';

    const STATUS_READY = 'ready';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_COMPLETED = 'completed';

    // Customer-initiated terminal state (customer self-cancellation); not part of
    // the admin forward lifecycle below.
    const STATUS_CANCELLED_BY_CUSTOMER = 'cancelled_by_customer';

    const STATUS_REJECTED = 'rejected';

    // ??? Payment status constants ??????????????????????????????????
    const PAYMENT_UNPAID = 'unpaid';

    const PAYMENT_PAID = 'paid';

    const PAYMENT_REFUNDED = 'refunded';

    // Terminal marker for an order superseded by a customer modification; set by
    // OrderService::modifyOrder, outside the admin forward lifecycle below.
    const STATUS_MODIFIED = 'modified';

    /**
     * The strict admin-driven forward lifecycle. Each status maps to the ONLY
     * statuses an admin may move it to. Terminal states (completed, rejected,
     * cancelled_by_customer, modified) are absent → no outgoing transitions.
     *
     * This single source of truth enforces: no skipping steps and no going
     * backwards (a target not listed for the current status is rejected).
     */
    const ADMIN_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_ACCEPTED, self::STATUS_REJECTED],
        self::STATUS_ACCEPTED => [self::STATUS_READY],
        self::STATUS_READY => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED => [self::STATUS_COMPLETED],
    ];

    /**
     * Whether the order may transition from its current status to $to under the
     * strict admin lifecycle.
     */
    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::ADMIN_TRANSITIONS[$this->status] ?? [], true);
    }

    protected $fillable = [
        'order_number',
        'customer_id',
        'modified_from_order_id',
        'customer_name',
        'phone',
        'source',
        'order_type',
        'table_number',
        'address',
        'delivery_type',
        'scheduled_at',
        'status',
        'subtotal',
        'estimated_delivery_fee',
        'delivery_fee',
        'discount',
        'total',
        'payment_status',
        'payment_method',
        'is_paid',
        'paid_at',
        'customer_note',
        'rejection_reason',
        'cancelled_at',
        'accepted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'estimated_delivery_fee' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ??? Relations ????????????????????????????????????????????????
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** The original order this one was modified from. */
    public function originalOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'modified_from_order_id');
    }

    /** The newer order that replaced this one. */
    public function modifiedOrder(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Order::class, 'modified_from_order_id');
    }

    // ??? Helpers ??????????????????????????????????????????????????
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED_BY_CUSTOMER;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPaid(): bool
    {
        return (bool) $this->is_paid;
    }

    /**
     * Generate a unique order number like ORD-20260503-0001.
     * The leading prefix is configurable via config/orders.php (ORDER_NUMBER_PREFIX).
     */
    public static function generateOrderNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = config('orders.number_prefix', 'ORD-').$today.'-';
        $last = self::where('order_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('order_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
