<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('weight_name')->nullable()->after('product_name');
            $table->decimal('weight_value_kg', 8, 3)->nullable()->after('weight_name');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['weight_name', 'weight_value_kg']);
        });
    }
};
