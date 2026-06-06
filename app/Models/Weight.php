<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Weight extends Model
{
    protected $fillable = [
        'name',
        'value_kg',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value_kg'   => 'decimal:3',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
