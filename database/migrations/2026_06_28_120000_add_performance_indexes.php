<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for the hot read paths (order lists, reports, dashboard,
 * the order-creation product lookup, and category-filtered product listings).
 *
 * Note: activity_logs already has (causer_type, causer_id) and
 * (subject_type, subject_id) indexes from nullableMorphs(), so they are not
 * repeated here. orders.customer_id and products.category_id already have
 * single-column FK indexes; the composites below extend them for the
 * filter+sort patterns actually used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Date-range filters (dashboard whereDate, reports whereBetween).
            $table->index('created_at');
            // GROUP BY order_type (reports doughnut, dashboard breakdown).
            $table->index('order_type');
            // status alone (leftmost) + status+date (reports, order list sort).
            $table->index(['status', 'created_at']);
            // Customer order history: filter by customer, sort by date.
            $table->index(['customer_id', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            // Availability filter on the order-creation transaction + API list.
            $table->index(['is_active', 'is_available']);
            // Category-scoped availability (admin + public category listings).
            $table->index(['category_id', 'is_active', 'is_available']);
            // Featured-products endpoint (low-cardinality, effective filter).
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['order_type']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['customer_id', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'is_available']);
            $table->dropIndex(['category_id', 'is_active', 'is_available']);
            $table->dropIndex(['is_featured']);
        });
    }
};
