<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->integer('min_points')->default(0);
            $table->integer('max_points')->nullable();
            $table->decimal('points_multiplier', 5, 2)->default(1.00)->comment('مضاعف النقاط (مثلاً 1.5 = 50% نقاط إضافية)');
            $table->decimal('discount_percent', 5, 2)->default(0)->comment('خصم إضافي %');
            $table->boolean('free_shipping')->default(false);
            $table->boolean('priority_support')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('badge')->nullable()->comment('رابط أيقونة المستوى');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
    }
};
