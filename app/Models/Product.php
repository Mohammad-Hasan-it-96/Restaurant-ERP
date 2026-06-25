<?php

namespace App\Models;

use Illuminate\Cache\TaggableStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    use HasFactory;

    /** Cache tag used for the API product-list responses (when the store supports tags). */
    public const LIST_CACHE_TAG = 'products';

    /** Cache key holding the list-cache version namespace (used when tags are unavailable). */
    public const LIST_CACHE_VERSION_KEY = 'products.cache_version';

    /**
     * Flush the cached API product-list responses on any create/update/delete.
     *
     * Every admin write path goes through Eloquent (save/update/delete/
     * updateOrCreate), so hooking the model events covers them all in one place.
     */
    protected static function booted(): void
    {
        static::saved(fn () => self::flushListCache());
        static::deleted(fn () => self::flushListCache());
    }

    /** Whether the active cache store supports tagging (redis/memcached/array do; database/file do not). */
    public static function listCacheSupportsTags(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }

    /** Current list-cache version; bumped on every write when tags aren't available. */
    public static function listCacheVersion(): int
    {
        return (int) Cache::get(self::LIST_CACHE_VERSION_KEY, 1);
    }

    /**
     * Remember a product-list payload, using cache tags when the store supports
     * them and falling back to a plain (version-namespaced) key otherwise.
     */
    public static function rememberList(string $key, int $ttl, \Closure $callback): mixed
    {
        if (self::listCacheSupportsTags()) {
            return Cache::tags(self::LIST_CACHE_TAG)->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Invalidate all cached product-list responses.
     *
     * With a taggable store we flush the tag. Without one (e.g. the database
     * driver) there is no way to enumerate keys, so we bump a version counter
     * baked into every key — old entries become unreachable and expire via TTL.
     */
    public static function flushListCache(): void
    {
        if (self::listCacheSupportsTags()) {
            Cache::tags(self::LIST_CACHE_TAG)->flush();

            return;
        }

        Cache::forever(self::LIST_CACHE_VERSION_KEY, self::listCacheVersion() + 1);
    }

    /**
     * Default attribute values — prevents DB "no default" errors for legacy columns.
     */
    protected $attributes = [
        'quantity' => 0,
        'sort_order' => 0,
    ];

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
        'is_weight_based',
        'price_per_kg',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'is_weight_based' => 'boolean',
            'price_per_kg' => 'decimal:2',
            'sort_order' => 'integer',
            'quantity' => 'integer',
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

    /**
     * Available weight options for this product (weight-based products only).
     */
    public function weights(): BelongsToMany
    {
        return $this->belongsToMany(Weight::class, 'product_weights');
    }

    /**
     * Option values assigned to this product (e.g. cutting type choices).
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(OptionValue::class, 'product_option_values');
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
