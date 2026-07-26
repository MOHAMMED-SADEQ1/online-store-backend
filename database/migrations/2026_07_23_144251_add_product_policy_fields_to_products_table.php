<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_returnable')->default(true)->after('is_featured');
            $table->boolean('is_exchangeable')->default(true)->after('is_returnable');
            $table->unsignedInteger('return_period_days')->default(14)->after('is_exchangeable');
            $table->boolean('is_cancellable')->default(true)->after('return_period_days');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_returnable', 'is_exchangeable', 'return_period_days', 'is_cancellable']);
        });
    }
};
