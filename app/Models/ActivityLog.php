<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ActivityLog — one immutable row per curated business event.
 *
 * Written exclusively through App\Services\ActivityLogger (the activity()
 * helper). Display relies on the denormalised causer_label / subject_label so
 * entries stay readable after the causer/subject is deleted; the morphTo
 * relations are a convenience for drilling through while the record still exists.
 */
class ActivityLog extends Model
{
    /** Immutable — we never update an audit row. */
    const UPDATED_AT = null;

    // ── Action constants (dot convention) ──────────────────────────────
    const ACTION_PRODUCT_CREATED = 'product.created';

    const ACTION_PRODUCT_UPDATED = 'product.updated';

    const ACTION_PRODUCT_DELETED = 'product.deleted';

    const ACTION_ORDER_ACCEPTED = 'order.accepted';

    const ACTION_ORDER_REJECTED = 'order.rejected';

    const ACTION_ORDER_READY = 'order.ready';

    const ACTION_ORDER_DELIVERED = 'order.delivered';

    const ACTION_ORDER_COMPLETED = 'order.completed';

    const ACTION_ORDER_PAID = 'order.paid';

    const ACTION_ORDER_PLACED = 'order.placed';

    const ACTION_ORDER_MODIFIED = 'order.modified';

    const ACTION_ORDER_CANCELLED = 'order.cancelled';

    const ACTION_SETTINGS_UPDATED = 'settings.updated';

    const ACTION_SETTINGS_CREATED = 'settings.created';

    const ACTION_SETTINGS_DELETED = 'settings.deleted';

    const ACTION_CUSTOMER_BLOCKED = 'customer.blocked';

    const ACTION_CUSTOMER_UNBLOCKED = 'customer.unblocked';

    protected $fillable = [
        'causer_type', 'causer_id', 'causer_label',
        'subject_type', 'subject_id', 'subject_label',
        'action', 'description', 'properties',
        'ip_address', 'request_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ─────────────────────────────────────────────────────────
    public function scopeAction(Builder $query, ?string $action): Builder
    {
        return $action ? $query->where('action', $action) : $query;
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
                ->orWhere('action', 'like', "%{$term}%")
                ->orWhere('causer_label', 'like', "%{$term}%")
                ->orWhere('subject_label', 'like', "%{$term}%");
        });
    }
}
