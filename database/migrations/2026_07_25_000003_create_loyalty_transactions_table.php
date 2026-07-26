<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // earned, spent, expired, adjusted, refunded
            $table->string('source'); // purchase, referral, signup, review, birthday, order_cancel, manual
            $table->integer('points');
            $table->integer('balance_after');
            $table->string('description_ar')->nullable();
            $table->string('description_en')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('تاريخ انتهاء صلاحية النقاط');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
