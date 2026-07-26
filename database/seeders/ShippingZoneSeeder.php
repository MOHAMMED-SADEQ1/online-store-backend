<?php

namespace Database\Seeders;

use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    public function run(): void
    {
        ShippingZone::firstOrCreate(
            ['name_en' => 'Riyadh Region'],
            [
                'name_ar'                => 'منطقة الرياض',
                'shipping_cost'          => 25.00,
                'free_shipping_threshold'=> 200.00,
                'is_active'              => true,
            ]
        );

        ShippingZone::firstOrCreate(
            ['name_en' => 'Makkah & Jeddah Region'],
            [
                'name_ar'                => 'منطقة مكة المكرمة وجدة',
                'shipping_cost'          => 30.00,
                'free_shipping_threshold'=> 250.00,
                'is_active'              => true,
            ]
        );

        ShippingZone::firstOrCreate(
            ['name_en' => 'Eastern Region'],
            [
                'name_ar'                => 'المنطقة الشرقية',
                'shipping_cost'          => 30.00,
                'free_shipping_threshold'=> 250.00,
                'is_active'              => true,
            ]
        );

        ShippingZone::firstOrCreate(
            ['name_en' => 'Other Regions'],
            [
                'name_ar'                => 'بقية المناطق',
                'shipping_cost'          => 40.00,
                'free_shipping_threshold'=> 300.00,
                'is_active'              => true,
            ]
        );

        $this->command->info('Shipping zones seeded: 4 zones created.');
    }
}
