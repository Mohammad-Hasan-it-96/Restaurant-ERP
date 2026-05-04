<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('categories')
                  ->nullOnDelete();
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->text('description_ar')->nullable()->after('details');
            $table->text('description_en')->nullable()->after('description_ar');
            $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            $table->string('image')->nullable()->after('discount_price');
            $table->boolean('is_available')->default(true)->after('image');
            $table->boolean('is_featured')->default(false)->after('is_available');
            $table->unsignedInteger('sort_order')->default(0)->after('is_featured');
            $table->boolean('is_active')->default(true)->after('sort_order');
            $table->string('slug')->nullable()->unique()->after('is_active');
        });
    }
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'category_id',
                'name_ar',
                'name_en',
                'description_ar',
                'description_en',
                'discount_price',
                'image',
                'is_available',
                'is_featured',
                'sort_order',
                'is_active',
                'slug',
            ]);
        });
    }
};
