<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->decimal('cost', 10, 2);
            $table->smallInteger('estimated_days_min')->default(1);
            $table->smallInteger('estimated_days_max')->default(5);
            $table->decimal('free_shipping_threshold', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_cities');
    }
};
