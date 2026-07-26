<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedTinyInteger('method_id')->nullable();
            $table->foreign('method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->string('payment_method', 100)->nullable();
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('payment_date')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'payment_status']);
            $table->index('transaction_id');
            $table->index('method_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
