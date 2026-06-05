<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_weight_based')->default(false)->after('is_active');
            $table->decimal('price_per_kg', 10, 2)->nullable()->after('is_weight_based');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_weight_based', 'price_per_kg']);
        });
    }
};
