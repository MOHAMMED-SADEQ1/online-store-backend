<?php

namespace Database\Seeders;

use App\Models\ShippingCity;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingCitySeeder extends Seeder
{
    public function run(): void
    {
        $zones = ShippingZone::all()->keyBy('name_en');

        $riyadhZone = $zones->get('Riyadh Region');
        $makkahZone = $zones->get('Makkah & Jeddah Region');
        $easternZone = $zones->get('Eastern Region');
        $otherZone = $zones->get('Other Regions');

        $cities = [];

        if ($riyadhZone) {
            $cities = array_merge($cities, [
                ['name_ar' => 'الرياض',      'name_en' => 'Riyadh',      'cost' => 20.00, 'estimated_days_min' => 1, 'estimated_days_max' => 2, 'free_shipping_threshold' => 200.00],
                ['name_ar' => 'الخرج',       'name_en' => 'Al Kharj',   'cost' => 25.00, 'estimated_days_min' => 1, 'estimated_days_max' => 3, 'free_shipping_threshold' => 200.00],
                ['name_ar' => 'المجمعة',     'name_en' => 'Al Majmaah', 'cost' => 30.00, 'estimated_days_min' => 2, 'estimated_days_max' => 4, 'free_shipping_threshold' => 200.00],
                ['name_ar' => 'الدوادمي',    'name_en' => 'Al Duwadmi', 'cost' => 30.00, 'estimated_days_min' => 2, 'estimated_days_max' => 4, 'free_shipping_threshold' => 200.00],
            ]);
        }

        if ($makkahZone) {
            $cities = array_merge($cities, [
                ['name_ar' => 'جدة',         'name_en' => 'Jeddah',       'cost' => 25.00, 'estimated_days_min' => 1, 'estimated_days_max' => 2, 'free_shipping_threshold' => 250.00],
                ['name_ar' => 'مكة المكرمة',  'name_en' => 'Makkah',      'cost' => 25.00, 'estimated_days_min' => 1, 'estimated_days_max' => 2, 'free_shipping_threshold' => 250.00],
                ['name_ar' => 'الطائف',       'name_en' => 'Taif',        'cost' => 30.00, 'estimated_days_min' => 2, 'estimated_days_max' => 3, 'free_shipping_threshold' => 250.00],
                ['name_ar' => 'القنفذة',      'name_en' => 'Al Qunfudhah','cost' => 35.00, 'estimated_days_min' => 2, 'estimated_days_max' => 4, 'free_shipping_threshold' => 250.00],
            ]);
        }

        if ($easternZone) {
            $cities = array_merge($cities, [
                ['name_ar' => 'الدمام',    'name_en' => 'Dammam',    'cost' => 25.00, 'estimated_days_min' => 1, 'estimated_days_max' => 2, 'free_shipping_threshold' => 250.00],
                ['name_ar' => 'الخبر',     'name_en' => 'Al Khobar', 'cost' => 25.00, 'estimated_days_min' => 1, 'estimated_days_max' => 2, 'free_shipping_threshold' => 250.00],
                ['name_ar' => 'الظهران',   'name_en' => 'Dhahran',   'cost' => 25.00, 'estimated_days_min' => 1, 'estimated_days_max' => 2, 'free_shipping_threshold' => 250.00],
                ['name_ar' => 'الأحساء',   'name_en' => 'Al Ahsa',   'cost' => 30.00, 'estimated_days_min' => 2, 'estimated_days_max' => 3, 'free_shipping_threshold' => 250.00],
            ]);
        }

        if ($otherZone) {
            $cities = array_merge($cities, [
                ['name_ar' => 'المدينة المنورة', 'name_en' => 'Madinah',     'cost' => 30.00, 'estimated_days_min' => 2, 'estimated_days_max' => 3, 'free_shipping_threshold' => 300.00],
                ['name_ar' => 'القصيم',         'name_en' => 'Al Qassim',    'cost' => 30.00, 'estimated_days_min' => 2, 'estimated_days_max' => 3, 'free_shipping_threshold' => 300.00],
                ['name_ar' => 'تبوك',           'name_en' => 'Tabuk',        'cost' => 35.00, 'estimated_days_min' => 2, 'estimated_days_max' => 4, 'free_shipping_threshold' => 300.00],
                ['name_ar' => 'حائل',           'name_en' => 'Hail',         'cost' => 35.00, 'estimated_days_min' => 2, 'estimated_days_max' => 4, 'free_shipping_threshold' => 300.00],
                ['name_ar' => 'عسير (أبها)',     'name_en' => 'Abha',         'cost' => 35.00, 'estimated_days_min' => 2, 'estimated_days_max' => 4, 'free_shipping_threshold' => 300.00],
                ['name_ar' => 'نجران',           'name_en' => 'Najran',       'cost' => 40.00, 'estimated_days_min' => 3, 'estimated_days_max' => 5, 'free_shipping_threshold' => 300.00],
                ['name_ar' => 'جازان',           'name_en' => 'Jazan',        'cost' => 40.00, 'estimated_days_min' => 3, 'estimated_days_max' => 5, 'free_shipping_threshold' => 300.00],
                ['name_ar' => 'الباحة',          'name_en' => 'Al Baha',      'cost' => 40.00, 'estimated_days_min' => 3, 'estimated_days_max' => 5, 'free_shipping_threshold' => 300.00],
                ['name_ar' => 'الحدود الشمالية', 'name_en' => 'Northern Borders', 'cost' => 40.00, 'estimated_days_min' => 3, 'estimated_days_max' => 5, 'free_shipping_threshold' => 300.00],
                ['name_ar' => 'عرعر',            'name_en' => 'Arar',         'cost' => 45.00, 'estimated_days_min' => 3, 'estimated_days_max' => 5, 'free_shipping_threshold' => 300.00],
            ]);
        }

        foreach ($cities as $city) {
            $zoneId = null;
            if ($riyadhZone && in_array($city['name_en'], ['Riyadh','Al Kharj','Al Majmaah','Al Duwadmi'])) {
                $zoneId = $riyadhZone->id;
            } elseif ($makkahZone && in_array($city['name_en'], ['Jeddah','Makkah','Taif','Al Qunfudhah'])) {
                $zoneId = $makkahZone->id;
            } elseif ($easternZone && in_array($city['name_en'], ['Dammam','Al Khobar','Dhahran','Al Ahsa'])) {
                $zoneId = $easternZone->id;
            } elseif ($otherZone) {
                $zoneId = $otherZone->id;
            }

            ShippingCity::firstOrCreate(
                ['name_en' => $city['name_en']],
                array_merge($city, ['shipping_zone_id' => $zoneId, 'is_active' => true])
            );
        }

        $this->command->info('Shipping cities seeded: ' . count($cities) . ' cities created.');
    }
}
