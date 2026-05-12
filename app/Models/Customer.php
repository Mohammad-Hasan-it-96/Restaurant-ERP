<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Customer extends Model
{
    protected $fillable = [
        'full_name',
        'phone',
        'default_address',
        'is_blocked',
        'blocked_reason',
    ];
    protected function casts(): array
    {
        return [
            'is_blocked' => 'boolean',
        ];
    }
    // ??? Relations ????????????????????????????????????????????????
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    // ── Accessors ─────────────────────────────────────────────────────────────
    /**
     * Sum of all order totals for this customer.
     * Prefer using withSum('orders','total') on the query for bulk lists.
     */
    public function getTotalSpentAttribute(): float
    {
        return (float) ($this->orders_sum_total ?? $this->orders()->sum('total'));
    }

    // ── Scopes ────────────────────────────────────────────────────────────────
    /**
     * Scope to non-blocked customers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }
}
