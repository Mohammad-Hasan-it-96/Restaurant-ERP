<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    /** Cache key for the public /api/v1/categories list (locale-independent models). */
    const PUBLIC_CACHE_KEY = 'categories.api.public';

    protected $fillable = [
        'name_ar',
        'name_en',
        'image',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    /**
     * Categories are read on nearly every public/admin page but change rarely,
     * so flush the cached list whenever one is created/updated/deleted.
     */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::PUBLIC_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::PUBLIC_CACHE_KEY));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ??? Relations ????????????????????????????????????????????????
    /**
     * Parent category (self-referencing).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Direct child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * All products belonging to this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // ??? Helpers ??????????????????????????????????????????????????
    /**
     * Returns the translatable display name based on app locale.
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar'
            ? ($this->name_ar ?? $this->name_en ?? '')
            : ($this->name_en ?? $this->name_ar ?? '');
    }

    /**
     * Scope to only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only root categories (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
