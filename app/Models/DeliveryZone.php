<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DeliveryZone extends Model
{
    protected $fillable = [
        'area_name',
        'estimated_fee',
        'sort_order',
        'is_active',
    ];
    protected function casts(): array
    {
        return [
            'estimated_fee' => 'decimal:2',
            'sort_order'    => 'integer',
            'is_active'     => 'boolean',
        ];
    }
    // ??? Helpers ??????????????????????????????????????????????????
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
