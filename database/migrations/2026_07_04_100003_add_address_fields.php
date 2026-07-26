<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('building_number', 50)->nullable()->after('is_default');
            $table->string('floor_number', 10)->nullable()->after('building_number');
            $table->string('apartment_number', 50)->nullable()->after('floor_number');
            $table->text('additional_directions')->nullable()->after('apartment_number');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'building_number',
                'floor_number',
                'apartment_number',
                'additional_directions',
            ]);
        });
    }
};