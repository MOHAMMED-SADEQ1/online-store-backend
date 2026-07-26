<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('is_free_shipping')->default(false)->after('exclude_sale_items');
            $table->unsignedInteger('per_user_limit')->nullable()->after('usage_limit');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete()->after('per_user_limit');
            $table->unsignedInteger('min_orders_count')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['is_free_shipping', 'per_user_limit', 'min_orders_count']);
        });
    }
};