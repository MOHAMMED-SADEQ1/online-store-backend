<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('return_requests', 'exchange_items')) {
                $table->json('exchange_items')->nullable()->after('refund_amount');
            }
            if (!Schema::hasColumn('return_requests', 'exchange_order_id')) {
                $table->foreignId('exchange_order_id')->nullable()->after('exchange_items')->constrained('orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropForeign(['exchange_order_id']);
            $table->dropColumn(['exchange_items', 'exchange_order_id']);
        });
    }
};
