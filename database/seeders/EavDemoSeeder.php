<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class EavDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // 1. Create Attributes and their Values (EAV)
        // ============================================================

        $color = Attribute::firstOrCreate(
            ['name_en' => 'Color'],
            ['name_ar' => 'اللون', 'attribute_type' => 'color', 'display_order' => 1, 'is_global' => true]
        );
        $size = Attribute::firstOrCreate(
            ['name_en' => 'Size'],
            ['name_ar' => 'المقاس', 'attribute_type' => 'size', 'display_order' => 2, 'is_global' => true]
        );
        $material = Attribute::firstOrCreate(
            ['name_en' => 'Material'],
            ['name_ar' => 'الخامة', 'attribute_type' => 'select', 'display_order' => 3, 'is_global' => true]
        );

        // Color values
        $red = AttributeValue::firstOrCreate(['value_en' => 'Red'],     ['attribute_id' => $color->id, 'value_ar' => 'أحمر', 'extra_data' => ['hex' => '#FF0000'], 'display_order' => 1]);
        $blue = AttributeValue::firstOrCreate(['value_en' => 'Blue'],   ['attribute_id' => $color->id, 'value_ar' => 'أزرق', 'extra_data' => ['hex' => '#0000FF'], 'display_order' => 2]);
        $green = AttributeValue::firstOrCreate(['value_en' => 'Green'], ['attribute_id' => $color->id, 'value_ar' => 'أخضر', 'extra_data' => ['hex' => '#00FF00'], 'display_order' => 3]);

        // Size values
        $small = AttributeValue::firstOrCreate(['value_en' => 'Small'],  ['attribute_id' => $size->id, 'value_ar' => 'صغير', 'display_order' => 1]);
        $medium = AttributeValue::firstOrCreate(['value_en' => 'Medium'], ['attribute_id' => $size->id, 'value_ar' => 'وسط', 'display_order' => 2]);
        $large = AttributeValue::firstOrCreate(['value_en' => 'Large'],  ['attribute_id' => $size->id, 'value_ar' => 'كبير', 'display_order' => 3]);

        // Material values
        $cotton = AttributeValue::firstOrCreate(['value_en' => 'Cotton'],    ['attribute_id' => $material->id, 'value_ar' => 'قطن', 'display_order' => 1]);
        $polyester = AttributeValue::firstOrCreate(['value_en' => 'Polyester'], ['attribute_id' => $material->id, 'value_ar' => 'بوليستر', 'display_order' => 2]);

        // ============================================================
        // 2. Create a Category
        // ============================================================

        $category = Category::firstOrCreate(
            ['slug' => 'mens-clothing'],
            ['name_ar' => 'ملابس رجالية', 'name_en' => "Men's Clothing", 'description_ar' => 'تشكيلة واسعة من الملابس الرجالية', 'description_en' => 'A wide range of men\'s clothing', 'is_active' => true]
        );

        // ============================================================
        // 3. Create Products (skip if already exists)
        // ============================================================

        $firstRun = Product::where('slug', 'classic-mens-shirt')->doesntExist();
        if (!$firstRun) {
            $this->command->info('EAV Demo data already exists — nothing to seed.');
            return;
        }

        $product = Product::create([
            'name_ar' => 'قميص رجالي كلاسيك',
            'name_en' => 'Classic Men\'s Shirt',
            'slug' => 'classic-mens-shirt',
            'description_ar' => 'قميص رجالي كلاسيك عالي الجودة مناسب لجميع المناسبات',
            'description_en' => 'High-quality classic men\'s shirt suitable for all occasions',
            'sku' => 'SHIRT-CLS-001',
            'regular_price' => 199.99,
            'sale_price' => 149.99,
            'cost_price' => 80.00,
            'quantity_in_stock' => 100,
            'low_stock_threshold' => 10,
            'weight' => 0.30,
            'dimensions' => '30x20x2 cm',
            'main_image' => 'https://placehold.co/600x600?text=Classic+Shirt',
            'is_active' => true,
            'is_featured' => true,
        ]);
        $product->categories()->sync([$category->id]);

        $product->attributes()->sync([
            $color->id    => ['is_variation' => true, 'display_order' => 1],
            $size->id     => ['is_variation' => true, 'display_order' => 2],
            $material->id => ['is_variation' => true, 'display_order' => 3],
        ]);

        $combinations = [
            ['SHIRT-CLS-RD-SM-CT', 149.99, 10, [$red->id, $small->id, $cotton->id]],
            ['SHIRT-CLS-RD-SM-PL', 139.99, 8,  [$red->id, $small->id, $polyester->id]],
            ['SHIRT-CLS-RD-MD-CT', 149.99, 15, [$red->id, $medium->id, $cotton->id]],
            ['SHIRT-CLS-RD-MD-PL', 139.99, 12, [$red->id, $medium->id, $polyester->id]],
            ['SHIRT-CLS-RD-LG-CT', 159.99, 7,  [$red->id, $large->id, $cotton->id]],
            ['SHIRT-CLS-RD-LG-PL', 149.99, 5,  [$red->id, $large->id, $polyester->id]],
            ['SHIRT-CLS-BL-SM-CT', 149.99, 20, [$blue->id, $small->id, $cotton->id]],
            ['SHIRT-CLS-BL-SM-PL', 139.99, 14, [$blue->id, $small->id, $polyester->id]],
            ['SHIRT-CLS-BL-MD-CT', 149.99, 25, [$blue->id, $medium->id, $cotton->id]],
            ['SHIRT-CLS-BL-MD-PL', 139.99, 18, [$blue->id, $medium->id, $polyester->id]],
            ['SHIRT-CLS-BL-LG-CT', 159.99, 9,  [$blue->id, $large->id, $cotton->id]],
            ['SHIRT-CLS-BL-LG-PL', 149.99, 6,  [$blue->id, $large->id, $polyester->id]],
            ['SHIRT-CLS-GR-SM-CT', 149.99, 12, [$green->id, $small->id, $cotton->id]],
            ['SHIRT-CLS-GR-SM-PL', 139.99, 10, [$green->id, $small->id, $polyester->id]],
            ['SHIRT-CLS-GR-MD-CT', 149.99, 18, [$green->id, $medium->id, $cotton->id]],
            ['SHIRT-CLS-GR-MD-PL', 139.99, 14, [$green->id, $medium->id, $polyester->id]],
            ['SHIRT-CLS-GR-LG-CT', 159.99, 8,  [$green->id, $large->id, $cotton->id]],
            ['SHIRT-CLS-GR-LG-PL', 149.99, 4,  [$green->id, $large->id, $polyester->id]],
        ];

        foreach ($combinations as [$sku, $price, $stock, $valueIds]) {
            $variant = ProductVariant::create([
                'product_id' => $product->id, 'sku' => $sku, 'regular_price' => $price,
                'sale_price' => null, 'cost_price' => 60.00, 'stock_quantity' => $stock,
                'barcode' => null, 'is_active' => true,
            ]);
            $variant->attributeValues()->sync($valueIds);
        }

        // Second product (Size only)
        $product2 = Product::create([
            'name_ar' => 'قبعة بيسبول', 'name_en' => 'Baseball Cap',
            'slug' => 'baseball-cap',
            'description_ar' => 'قبعة بيسبول عصرية بتصميم أنيق',
            'description_en' => 'Stylish modern baseball cap',
            'sku' => 'CAP-BB-001', 'regular_price' => 79.99, 'sale_price' => 59.99,
            'cost_price' => 25.00, 'quantity_in_stock' => 50, 'low_stock_threshold' => 5,
            'weight' => 0.10, 'dimensions' => '15x10x5 cm',
            'main_image' => 'https://placehold.co/600x600?text=Baseball+Cap',
            'is_active' => true, 'is_featured' => false,
        ]);
        $product2->categories()->sync([$category->id]);
        $product2->attributes()->sync([$size->id => ['is_variation' => true, 'display_order' => 1]]);

        foreach ([
            ['CAP-BB-SM', 59.99, 20, [$small->id]],
            ['CAP-BB-MD', 59.99, 25, [$medium->id]],
            ['CAP-BB-LG', 64.99, 15, [$large->id]],
        ] as [$sku, $price, $stock, $valueIds]) {
            $variant = ProductVariant::create([
                'product_id' => $product2->id, 'sku' => $sku, 'regular_price' => $price,
                'sale_price' => null, 'cost_price' => 25.00, 'stock_quantity' => $stock,
                'barcode' => null, 'is_active' => true,
            ]);
            $variant->attributeValues()->sync($valueIds);
        }

        $this->command->info("EAV Demo seeded: Product 1 has {$product->variants()->count()} variants, Product 2 has {$product2->variants()->count()} variants.");
    }
}
