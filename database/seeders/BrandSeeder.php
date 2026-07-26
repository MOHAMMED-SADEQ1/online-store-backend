<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            // ============================================================
            // أشهر ماركات العود والعطور الشرقية
            // ============================================================
            [
                'name_ar' => 'عبد الصمد القرشي',
                'name_en' => 'Abdul Samad Al Qurashi',
                'slug'    => 'abdul-samad-al-qurashi',
                'description_ar' => 'علامة تجارية سعودية عريقة تأسست عام 1852، متخصصة في أجود أنواع العود والعطور الشرقية.',
                'description_en' => 'A prestigious Saudi brand established in 1852, specializing in the finest oud and oriental perfumes.',
                'is_active' => true,
            ],
            [
                'name_ar' => 'الماجد للعود',
                'name_en' => 'Al Majed Oud',
                'slug'    => 'al-majed-oud',
                'description_ar' => 'علامة سعودية رائدة في عالم العود والعطور العربية منذ عام 1978.',
                'description_en' => 'A leading Saudi brand in the world of oud and Arabic perfumes since 1978.',
                'is_active' => true,
            ],
            [
                'name_ar' => 'أجمل',
                'name_en' => 'Ajmal',
                'slug'    => 'ajmal',
                'description_ar' => 'علامة تجارية إماراتية عريقة متخصصة في العطور الشرقية والغربية منذ 1951.',
                'description_en' => 'A historic Emirati brand specializing in oriental and western perfumes since 1951.',
                'is_active' => true,
            ],
            [
                'name_ar' => 'رأس العود',
                'name_en' => 'Rasasi',
                'slug'    => 'rasasi',
                'description_ar' => 'علامة تجارية إماراتية تأسست عام 1979، تشتهر بعطورها الشرقية الفاخرة.',
                'description_en' => 'An Emirati brand founded in 1979, renowned for its luxurious oriental fragrances.',
                'is_active' => true,
            ],
            [
                'name_ar' => 'العربية للعود',
                'name_en' => 'Arabian Oud',
                'slug'    => 'arabian-oud',
                'description_ar' => 'أكبر سلسلة متاجر للعود والعطور في الشرق الأوسط، تأسست عام 1982.',
                'description_en' => 'The largest chain of oud and perfume stores in the Middle East, founded in 1982.',
                'is_active' => true,
            ],
            [
                'name_ar' => 'سويس أريبيان',
                'name_en' => 'Swiss Arabian',
                'slug'    => 'swiss-arabian',
                'description_ar' => 'علامة تجارية تجمع بين المهارات السويسرية والروح العربية في صناعة العطور.',
                'description_en' => 'A brand that combines Swiss craftsmanship with Arabian essence in perfumery.',
                'is_active' => true,
            ],
            [
                'name_ar' => 'خلود',
                'name_en' => 'Khaloud',
                'slug'    => 'khaloud',
                'description_ar' => 'علامة تجارية سعودية متخصصة في دهن العود والعطور العربية الأصيلة.',
                'description_en' => 'A Saudi brand specializing in pure oud oil and authentic Arabic perfumes.',
                'is_active' => true,
            ],
            [
                'name_ar' => 'منزل العود',
                'name_en' => 'House of Oud',
                'slug'    => 'house-of-oud',
                'description_ar' => 'علامة عالمية فاخرة متخصصة في العود والعطور النادرة.',
                'description_en' => 'A global luxury brand specializing in rare oud and perfumes.',
                'is_active' => true,
            ],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['slug' => $brand['slug']],
                $brand
            );
        }

        $this->command->info('Brands seeded: ' . count($brands) . ' brands created.');
    }
}
