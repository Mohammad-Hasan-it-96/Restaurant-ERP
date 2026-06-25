<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalize order statuses to the strict lifecycle:
 *   pending → accepted → ready → delivered → completed   (+ rejected)
 *
 * Removed statuses are remapped onto kept ones:
 *   preparing            → accepted   (was the step right after accepted)
 *   cancelled            → rejected   (legacy generic cancel)
 *   cancelled_by_admin   → rejected   (admin-side "no", same meaning as rejected)
 *
 * Kept untouched: cancelled_by_customer (customer self-cancel) and modified
 * (order superseded by a customer modification).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('status', 'preparing')->update(['status' => 'accepted']);
        DB::table('orders')->where('status', 'cancelled')->update(['status' => 'rejected']);
        DB::table('orders')->where('status', 'cancelled_by_admin')->update(['status' => 'rejected']);
    }

    public function down(): void
    {
        // Irreversible: the original distinction between rejected / cancelled /
        // cancelled_by_admin and between accepted / preparing is not recoverable.
    }
};
