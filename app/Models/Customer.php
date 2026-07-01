<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    /**
     * The plaintext Bearer token, populated only at the moment a token is issued
     * (see issueToken()). Never persisted — the DB stores only its hash — so it
     * can be returned to the client exactly once. Null on any model loaded from DB.
     */
    public ?string $plainTextToken = null;

    /**
     * Mass-assignable attributes.
     *
     * `token`, `is_blocked` and `blocked_reason` are deliberately EXCLUDED so a
     * customer can never set their own auth token or self-unblock through a
     * fill()/create()/update() with request data. The token is written only via
     * issueToken() (which sets it directly on the model); block state is written
     * only by explicit assignment in Admin\CustomerController.
     */
    protected $fillable = [
        'full_name',
        'phone',
        'default_address',
        'fcm_token',
    ];

    protected function casts(): array
    {
        return [
            'is_blocked' => 'boolean',
        ];
    }

    // ── Auth token (stored hashed) ─────────────────────────────────────────────

    /**
     * Hash a plaintext Bearer token for storage/lookup. Tokens are kept as a
     * SHA-256 hash (not plaintext) so a DB/backup/log leak can't expose a usable
     * credential. SHA-256 (not bcrypt) keeps lookup an indexed equality match.
     */
    public static function hashToken(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * Issue a fresh token: store its hash on the model (caller must save()) and
     * stash the plaintext on $plainTextToken so it can be returned to the client
     * this one time. Returns the plaintext.
     */
    public function issueToken(): string
    {
        $plain = (string) Str::uuid();
        $this->plainTextToken = $plain;
        $this->token = self::hashToken($plain);

        return $plain;
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
