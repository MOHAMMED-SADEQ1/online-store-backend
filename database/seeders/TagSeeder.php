<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // ── وسوم عامة ──
            ['name_ar' => 'الأكثر مبيعاً', 'name_en' => 'Best Seller',      'slug' => 'best-seller'],
            ['name_ar' => 'وصل حديثاً',    'name_en' => 'New Arrival',      'slug' => 'new-arrival'],
            ['name_ar' => 'عرض خاص',       'name_en' => 'Special Offer',    'slug' => 'special-offer'],
            ['name_ar' => 'ممتاز',         'name_en' => 'Premium',          'slug' => 'premium'],

            // ── وسوم حسب النوع ──
            ['name_ar' => 'عطر رجالي',     'name_en' => 'Men Perfume',      'slug' => 'men-perfume'],
            ['name_ar' => 'عطر نسائي',     'name_en' => 'Women Perfume',    'slug' => 'women-perfume'],
            ['name_ar' => 'عطر للجنسين',   'name_en' => 'Unisex Perfume',   'slug' => 'unisex-perfume'],
            ['name_ar' => 'دهن عود طبيعي',  'name_en' => 'Natural Oud Oil', 'slug' => 'natural-oud-oil'],

            // ── وسوم حسب المناسبة ──
            ['name_ar' => 'هدية رمضان',    'name_en' => 'Ramadan Gift',     'slug' => 'ramadan-gift'],
            ['name_ar' => 'هدية العيد',    'name_en' => 'Eid Gift',         'slug' => 'eid-gift'],
            ['name_ar' => 'هدية زفاف',     'name_en' => 'Wedding Gift',     'slug' => 'wedding-gift'],
            ['name_ar' => 'مناسبة خاصة',   'name_en' => 'Special Occasion', 'slug' => 'special-occasion'],
            ['name_ar' => 'للاستخدام اليومي', 'name_en' => 'Daily Use',     'slug' => 'daily-use'],

            // ── وسوم حسب المكونات ──
            ['name_ar' => 'عود',           'name_en' => 'Oud',              'slug' => 'oud-tag'],
            ['name_ar' => 'مسك',           'name_en' => 'Musk',             'slug' => 'musk'],
            ['name_ar' => 'عنبر',          'name_en' => 'Amber',            'slug' => 'amber'],
            ['name_ar' => 'ورد',           'name_en' => 'Rose',             'slug' => 'rose'],
            ['name_ar' => 'بخور',          'name_en' => 'Bakhour',          'slug' => 'bakhour-tag'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['slug' => $tag['slug']],
                $tag
            );
        }

        $this->command->info('Tags seeded: ' . count($tags) . ' tags created.');
    }
}
