<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds explicit payment fields to orders:
 *   is_paid  (boolean)  — authoritative "has this order been paid" flag
 *   paid_at  (datetime) — when payment was recorded
 *
 * The legacy payment_status / payment_method columns are kept (the dashboard,
 * reports and SPA still read them) and stay in sync with is_paid going forward.
 * Existing rows already marked payment_status = 'paid' are backfilled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('payment_method');
            }
            if (! Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('is_paid');
            }
        });

        // Backfill from the legacy flag; use updated_at as the best-effort paid time.
        DB::table('orders')
            ->where('payment_status', 'paid')
            ->update([
                'is_paid' => true,
                'paid_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'paid_at']);
        });
    }
};
