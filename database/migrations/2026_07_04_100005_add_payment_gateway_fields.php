<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_methods', 'gateway')) {
                $table->string('gateway', 50)->default('moyasar')->after('name_en');
            }
            if (!Schema::hasColumn('payment_methods', 'is_online')) {
                $table->boolean('is_online')->default(true)->after('gateway');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'gateway')) {
                $table->string('gateway', 50)->nullable()->after('method_id');
            }
            if (!Schema::hasColumn('payments', 'callback_url')) {
                $table->text('callback_url')->nullable()->after('gateway_response');
            }
            if (!Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('callback_url');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_method_id')) {
                $table->unsignedTinyInteger('payment_method_id')->nullable()->after('payment_status');
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'callback_url')) {
                $table->text('callback_url')->nullable()->after('payment_method_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn(['payment_method_id', 'callback_url']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'callback_url', 'paid_at']);
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'is_online']);
        });
    }
};
