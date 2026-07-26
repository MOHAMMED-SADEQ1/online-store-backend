<?php

namespace Database\Seeders;

use App\Models\LoyaltyTier;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LoyaltyTierSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        LoyaltyTier::insert([
            [
                'name_ar'          => 'برونزي',
                'name_en'          => 'Bronze',
                'slug'             => 'bronze',
                'min_points'       => 0,
                'max_points'       => 999,
                'points_multiplier'=> 1.00,
                'discount_percent' => 0,
                'free_shipping'    => false,
                'priority_support' => false,
                'is_active'        => true,
                'badge'            => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'name_ar'          => 'فضي',
                'name_en'          => 'Silver',
                'slug'             => 'silver',
                'min_points'       => 1000,
                'max_points'       => 4999,
                'points_multiplier'=> 1.25,
                'discount_percent' => 5,
                'free_shipping'    => false,
                'priority_support' => false,
                'is_active'        => true,
                'badge'            => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'name_ar'          => 'ذهبي',
                'name_en'          => 'Gold',
                'slug'             => 'gold',
                'min_points'       => 5000,
                'max_points'       => 19999,
                'points_multiplier'=> 1.50,
                'discount_percent' => 10,
                'free_shipping'    => true,
                'priority_support' => false,
                'is_active'        => true,
                'badge'            => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'name_ar'          => 'بلاتيني',
                'name_en'          => 'Platinum',
                'slug'             => 'platinum',
                'min_points'       => 20000,
                'max_points'       => null,
                'points_multiplier'=> 2.00,
                'discount_percent' => 15,
                'free_shipping'    => true,
                'priority_support' => true,
                'is_active'        => true,
                'badge'            => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ]);
    }
}
