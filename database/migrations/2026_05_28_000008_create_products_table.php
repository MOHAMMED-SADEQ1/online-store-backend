<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 500);
            $table->string('name_en', 500);
            $table->string('slug')->nullable()->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('sku', 100)->unique();
            $table->decimal('regular_price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('low_stock_threshold')->default(0);
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->string('main_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index('is_active');
            $table->index('is_featured');
            $table->index('tax_rate_id');
            $table->fullText(['name_ar', 'name_en', 'description_ar', 'description_en'], 'ft_products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
