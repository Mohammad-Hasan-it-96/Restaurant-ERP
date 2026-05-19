<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Self-referencing FK: new order → original order
            $table->unsignedBigInteger('modified_from_order_id')->nullable()->after('customer_id');
            $table->foreign('modified_from_order_id')
                  ->references('id')
                  ->on('orders')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['modified_from_order_id']);
            $table->dropColumn('modified_from_order_id');
        });
    }
};

