<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Order extends Model
{
    // ??? Order type constants ??????????????????????????????????????
    const TYPE_TABLE    = 'table';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_TAKEAWAY = 'takeaway';
    // ??? Status constants ??????????????????????????????????????????
    const STATUS_PENDING              = 'pending';
    const STATUS_ACCEPTED             = 'accepted';
    const STATUS_PREPARING            = 'preparing';
    const STATUS_READY                = 'ready';
    const STATUS_DELIVERED            = 'delivered';
    const STATUS_COMPLETED            = 'completed';
    const STATUS_CANCELLED            = 'cancelled';              // legacy / generic
    const STATUS_CANCELLED_BY_ADMIN   = 'cancelled_by_admin';
    const STATUS_CANCELLED_BY_CUSTOMER= 'cancelled_by_customer';
    const STATUS_REJECTED             = 'rejected';
    // ??? Payment status constants ??????????????????????????????????
    const PAYMENT_UNPAID = 'unpaid';
    const PAYMENT_PAID   = 'paid';
    const PAYMENT_REFUNDED = 'refunded';
    protected $fillable = [
        'order_number',
        'customer_id',
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
        'customer_note',
        'rejection_reason',
        'cancelled_at',
        'accepted_at',
        'completed_at',
    ];
    protected function casts(): array
    {
        return [
            'subtotal'               => 'decimal:2',
            'estimated_delivery_fee' => 'decimal:2',
            'delivery_fee'           => 'decimal:2',
            'discount'               => 'decimal:2',
            'total'                  => 'decimal:2',
            'scheduled_at'           => 'datetime',
            'cancelled_at'           => 'datetime',
            'accepted_at'            => 'datetime',
            'completed_at'           => 'datetime',
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
    // ??? Helpers ??????????????????????????????????????????????????
    public function isPending(): bool   { return $this->status === self::STATUS_PENDING; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isPaid(): bool      { return $this->payment_status === self::PAYMENT_PAID; }
    /**
     * Generate a unique order number like ORD-20260503-0001.
     */
    public static function generateOrderNumber(): string
    {
        $today  = now()->format('Ymd');
        $prefix = 'ORD-' . $today . '-';
        $last   = self::where('order_number', 'like', $prefix . '%')
                      ->orderByDesc('id')
                      ->value('order_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
