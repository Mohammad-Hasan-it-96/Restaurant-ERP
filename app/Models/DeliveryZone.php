<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DeliveryZone extends Model
{
    /** Cache key for the public /api/v1/delivery-zones list (locale-independent). */
    const PUBLIC_CACHE_KEY = 'delivery_zones.api.public';

    protected $fillable = [
        'area_name',
        'estimated_fee',
        'sort_order',
        'is_active',
    ];

    /**
     * Zones are read on every customer checkout but change rarely, so flush the
     * cached public list whenever one is created/updated/deleted.
     */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::PUBLIC_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::PUBLIC_CACHE_KEY));
    }

    protected function casts(): array
    {
        return [
            'estimated_fee' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ??? Helpers ??????????????????????????????????????????????????
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
