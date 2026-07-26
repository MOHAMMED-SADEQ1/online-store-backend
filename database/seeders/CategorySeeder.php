<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // الفئات الرئيسية (Main Categories)
        // ============================================================
        $oud = Category::firstOrCreate(
            ['slug' => 'oud'],
            [
                'name_ar'        => 'عود',
                'name_en'        => 'Oud',
                'description_ar' => 'أجود أنواع العود الطبيعي والمخلط، من أفضل الماركات العالمية',
                'description_en' => 'The finest natural and blended oud from the world\'s best brands',
                'display_order'  => 1,
                'is_active'      => true,
            ]
        );

        $perfumes = Category::firstOrCreate(
            ['slug' => 'perfumes'],
            [
                'name_ar'        => 'عطور',
                'name_en'        => 'Perfumes',
                'description_ar' => 'مجموعة واسعة من العطور الشرقية والغربية الفاخرة',
                'description_en' => 'A wide range of luxurious oriental and western perfumes',
                'display_order'  => 2,
                'is_active'      => true,
            ]
        );

        $incense = Category::firstOrCreate(
            ['slug' => 'incense'],
            [
                'name_ar'        => 'بخور',
                'name_en'        => 'Incense',
                'description_ar' => 'أفخر أنواع البخور والعود للتبخير والتعطير',
                'description_en' => 'The finest incense and oud for fragrance and fumigation',
                'display_order'  => 3,
                'is_active'      => true,
            ]
        );

        $gifts = Category::firstOrCreate(
            ['slug' => 'gifts'],
            [
                'name_ar'        => 'هدايا',
                'name_en'        => 'Gifts',
                'description_ar' => 'هدايا العطور والبخور الفاخرة، مثالية للمناسبات الخاصة',
                'description_en' => 'Luxury perfume and incense gifts, perfect for special occasions',
                'display_order'  => 4,
                'is_active'      => true,
            ]
        );

        $oils = Category::firstOrCreate(
            ['slug' => 'oud-oils'],
            [
                'name_ar'        => 'دهن عود',
                'name_en'        => 'Oud Oils',
                'description_ar' => 'أجود أنواع دهن العود الطبيعي الخام والمعتق',
                'description_en' => 'The finest natural aged oud oils',
                'display_order'  => 5,
                'is_active'      => true,
            ]
        );

        $blends = Category::firstOrCreate(
            ['slug' => 'blends'],
            [
                'name_ar'        => 'مخلطات',
                'name_en'        => 'Blends',
                'description_ar' => 'مخلطات عطرية فاخرة تجمع بين أفضل الروائح الشرقية والغربية',
                'description_en' => 'Luxury fragrance blends combining the best oriental and western scents',
                'display_order'  => 6,
                'is_active'      => true,
            ]
        );

        // ============================================================
        // الفئات الفرعية (Subcategories)
        // ============================================================

        // عود - فئات فرعية
        Category::firstOrCreate(
            ['slug' => 'oud-chips'],
            [
                'name_ar'        => 'عود بخور',
                'name_en'        => 'Oud Chips',
                'parent_id'      => $oud->id,
                'description_ar' => 'رقائق عود طبيعي للتبخير، درجات مختلفة من الجودة',
                'description_en' => 'Natural oud chips for burning, various grades of quality',
                'display_order'  => 1,
                'is_active'      => true,
            ]
        );

        Category::firstOrCreate(
            ['slug' => 'oud-oil-category'],
            [
                'name_ar'        => 'دهن عود',
                'name_en'        => 'Oud Oil',
                'parent_id'      => $oud->id,
                'description_ar' => 'دهن عود طبيعي خام بدرجات مختلفة',
                'description_en' => 'Pure natural oud oil in various grades',
                'display_order'  => 2,
                'is_active'      => true,
            ]
        );

        // عطور - فئات فرعية
        Category::firstOrCreate(
            ['slug' => 'arabic-perfumes'],
            [
                'name_ar'        => 'عطور شرقية',
                'name_en'        => 'Arabic Perfumes',
                'parent_id'      => $perfumes->id,
                'description_ar' => 'عطور شرقية أصيلة تجمع بين العود والورد والعنبر',
                'description_en' => 'Authentic oriental perfumes blending oud, rose, and amber',
                'display_order'  => 1,
                'is_active'      => true,
            ]
        );

        Category::firstOrCreate(
            ['slug' => 'western-perfumes'],
            [
                'name_ar'        => 'عطور غربية',
                'name_en'        => 'Western Perfumes',
                'parent_id'      => $perfumes->id,
                'description_ar' => 'عطور عالمية فاخرة من أشهر دور الأزياء',
                'description_en' => 'Luxury international perfumes from top fashion houses',
                'display_order'  => 2,
                'is_active'      => true,
            ]
        );

        Category::firstOrCreate(
            ['slug' => 'attar'],
            [
                'name_ar'        => 'عطر',
                'name_en'        => 'Attar',
                'parent_id'      => $perfumes->id,
                'description_ar' => 'عطور طبيعية مركزة خالية من الكحول',
                'description_en' => 'Concentrated natural perfumes, alcohol-free',
                'display_order'  => 3,
                'is_active'      => true,
            ]
        );

        // بخور - فئات فرعية
        Category::firstOrCreate(
            ['slug' => 'bakhour'],
            [
                'name_ar'        => 'بخور عربي',
                'name_en'        => 'Arabic Bakhour',
                'parent_id'      => $incense->id,
                'description_ar' => 'بخور عربي معطر بروائح العود والعنبر والمسك',
                'description_en' => 'Scented Arabic bakhour with oud, amber, and musk fragrances',
                'display_order'  => 1,
                'is_active'      => true,
            ]
        );

        Category::firstOrCreate(
            ['slug' => 'oud-burn'],
            [
                'name_ar'        => 'عود تبخير',
                'name_en'        => 'Burn Oud',
                'parent_id'      => $incense->id,
                'description_ar' => 'عود طبيعي للتبخير الفاخر في المناسبات',
                'description_en' => 'Natural oud for luxurious burning on special occasions',
                'display_order'  => 2,
                'is_active'      => true,
            ]
        );

        // هدايا - فئات فرعية
        Category::firstOrCreate(
            ['slug' => 'gift-sets'],
            [
                'name_ar'        => 'طقم هدايا',
                'name_en'        => 'Gift Sets',
                'parent_id'      => $gifts->id,
                'description_ar' => 'أطقم هدايا فاخرة تجمع بين العطور والبخور',
                'description_en' => 'Luxury gift sets combining perfumes and incense',
                'display_order'  => 1,
                'is_active'      => true,
            ]
        );

        Category::firstOrCreate(
            ['slug' => 'personalized-gifts'],
            [
                'name_ar'        => 'هدايا مخصصة',
                'name_en'        => 'Personalized Gifts',
                'parent_id'      => $gifts->id,
                'description_ar' => 'هدايا يمكن تخصيصها حسب الطلب',
                'description_en' => 'Customizable gifts made to order',
                'display_order'  => 2,
                'is_active'      => true,
            ]
        );

        $this->command->info('Categories seeded: full hierarchical category tree created.');
    }
}
