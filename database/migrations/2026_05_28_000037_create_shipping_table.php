<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('shipping_method', 100);
            $table->string('tracking_number', 100)->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('carrier', 100)->nullable();
            $table->foreignId('shipping_zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete();
            $table->timestamp('shipping_date')->nullable();
            $table->timestamp('estimated_delivery')->nullable();
            $table->timestamp('actual_delivery')->nullable();
            $table->enum('shipping_status', ['pending', 'shipped', 'in_transit', 'out_for_delivery', 'delivered'])->default('pending');
            $table->timestamps();

            $table->index('order_id');
            $table->index('tracking_number');
            $table->index(['carrier', 'shipping_status']);
            $table->index('shipping_zone_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping');
    }
};
