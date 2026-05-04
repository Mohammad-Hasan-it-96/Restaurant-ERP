<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_price',
        'quantity',
        'total',
    ];
    protected function casts(): array
    {
        return [
            'product_price' => 'decimal:2',
            'total'         => 'decimal:2',
            'quantity'      => 'integer',
        ];
    }
    // ??? Relations ????????????????????????????????????????????????
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withDefault([
            'name' => $this->product_name,
        ]);
    }
}
