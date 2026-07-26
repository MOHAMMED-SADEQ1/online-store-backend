<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('max_per_order')->nullable()->after('quantity_in_stock');
            $table->boolean('price_includes_tax')->default(false)->after('sale_price');
            $table->string('meta_title', 255)->nullable()->after('is_featured');
            $table->text('meta_description')->nullable()->after('meta_title');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['max_per_order', 'price_includes_tax', 'meta_title', 'meta_description']);
        });
    }
};
