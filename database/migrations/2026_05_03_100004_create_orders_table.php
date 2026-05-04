<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('source')->default('website');
            $table->string('order_type'); // table | delivery | takeaway
            $table->string('table_number')->nullable();
            $table->string('delivery_type')->nullable(); // immediate | scheduled
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('estimated_delivery_fee', 10, 2)->nullable();
            $table->decimal('delivery_fee', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable()->default(0);
            $table->decimal('total', 10, 2)->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->text('customer_note')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
