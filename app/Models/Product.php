<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        // ── Legacy fields (kept as-is) ──
        'name',
        'details',
        'price',
        'quantity',
        'user_id',
        // ── Restaurant fields ──
        'category_id',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'discount_price',
        'image',
        'is_available',
        'is_featured',
        'sort_order',
        'is_active',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'price'          => 'decimal:2',
            'discount_price' => 'decimal:2',
            'is_available'   => 'boolean',
            'is_featured'    => 'boolean',
            'is_active'      => 'boolean',
            'sort_order'     => 'integer',
            'quantity'       => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────

    /**
     * The admin user who created/owns this product.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The category this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Order line-items that reference this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Returns the effective selling price (discount_price if set, otherwise price).
     */
    public function getEffectivePriceAttribute(): string
    {
        return $this->discount_price ?? $this->price;
    }

    /**
     * Localised display name; falls back gracefully.
     */
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar'
            ? ($this->name_ar ?? $this->name_en ?? $this->name ?? '')
            : ($this->name_en ?? $this->name_ar ?? $this->name ?? '');
    }

    /**
     * Scope to available & active products only.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->where('is_available', true);
    }

    /**
     * Scope to featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
