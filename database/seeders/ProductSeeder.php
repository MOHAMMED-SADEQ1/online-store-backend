<?php

namespace Database\Seeders;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * صور تجريبية (placehold.co) – في الإنتاج استخدم روابط فعلية.
     */
    protected array $imagePlaceholders = [
        'oud'     => 'https://placehold.co/600x600/8B4513/FFFFFF?text=%D8%B9%D9%88%D8%AF',
        'perfume' => 'https://placehold.co/600x600/D4A574/FFFFFF?text=%D8%B9%D8%B7%D8%B1',
        'bakhour' => 'https://placehold.co/600x600/654321/FFFFFF?text=%D8%A8%D8%AE%D9%88%D8%B1',
        'oil'     => 'https://placehold.co/600x600/2F1B0E/FFFFFF?text=%D8%AF%D9%87%D9%86',
        'gift'    => 'https://placehold.co/600x600/C41E3A/FFFFFF?text=%D9%87%D8%AF%D9%8A%D8%A9',
        'blend'   => 'https://placehold.co/600x600/9B59B6/FFFFFF?text=%D9%85%D8%AE%D9%84%D8%B7',
    ];

    /**
     * slugs التي ينشئها هذا السيدر – للتحقق مما إذا كان قد ركض من قبل.
     */
    protected array $ourSlugs = [
        'oud-khmer', 'oud-hindi', 'oud-malaysian', 'oud-indonesian', 'oud-thai', 'oud-papuan', 'oud-super',
        'musk-tabriz', 'ward-al-madina', 'amber-royal', 'khaltat-al-majed', 'rasasi-al-ghutra',
        'swiss-musk', 'khaloud-al-sultan',
        'bakhour-malaki', 'bakhour-wardi', 'bakhour-oud-majestic', 'bakhour-turkish',
        'oud-chips-khmer', 'oud-chips-hindi', 'oud-chips-mix',
        'gift-luxury-oud', 'gift-perfume-collection', 'gift-bakhour-deluxe', 'gift-complete',
        'blend-oud-rose', 'blend-amber-musk',
        'western-oud-silk', 'western-night-oud',
    ];

    public function run(): void
    {
        // ── تخطٍ إذا كانت منتجاتنا موجودة مسبقاً ──
        if (Product::whereIn('slug', $this->ourSlugs)->exists()) {
            $this->command->info('ProductSeeder has already run — nothing to seed.');
            return;
        }

        // ── مراجع ──
        $brands     = Brand::all()->keyBy('slug');
        $categories = Category::all()->keyBy('slug');
        $taxRate    = TaxRate::first();

        $sizes   = AttributeValue::whereHas('attribute', fn($q) => $q->where('name_en', 'Size'))->get()->keyBy('value_en');
        $concs   = AttributeValue::whereHas('attribute', fn($q) => $q->where('name_en', 'Concentration'))->get()->keyBy('value_en');
        $types   = AttributeValue::whereHas('attribute', fn($q) => $q->where('name_en', 'Type'))->get()->keyBy('value_en');
        $oudTypes = AttributeValue::whereHas('attribute', fn($q) => $q->where('name_en', 'Oud Type'))->get()->keyBy('value_en');

        $attrSize = \App\Models\Attribute::where('name_en', 'Size')->first();
        $attrConc = \App\Models\Attribute::where('name_en', 'Concentration')->first();
        $attrType = \App\Models\Attribute::where('name_en', 'Type')->first();
        $attrOud  = \App\Models\Attribute::where('name_en', 'Oud Type')->first();

        $created = 0;

        // ══════════════════════════════════════════════════════════════
        // 1. دهن عود (Oud Oils)
        // ══════════════════════════════════════════════════════════════
        $created += $this->seedOudOils($brands, $categories, $taxRate, $sizes, $concs, $oudTypes, $attrSize, $attrConc, $attrOud);

        // ══════════════════════════════════════════════════════════════
        // 2. عطور شرقية (Arabic Perfumes)
        // ══════════════════════════════════════════════════════════════
        $created += $this->seedArabicPerfumes($brands, $categories, $taxRate, $sizes, $concs, $types, $attrSize, $attrConc, $attrType);

        // ══════════════════════════════════════════════════════════════
        // 3. بخور (Incense / Bakhour)
        // ══════════════════════════════════════════════════════════════
        $created += $this->seedBakhour($brands, $categories, $taxRate, $sizes, $attrSize);

        // ══════════════════════════════════════════════════════════════
        // 4. رقائق عود (Oud Chips)
        // ══════════════════════════════════════════════════════════════
        $created += $this->seedOudChips($brands, $categories, $taxRate, $sizes, $oudTypes, $attrSize, $attrOud);

        // ══════════════════════════════════════════════════════════════
        // 5. أطقم هدايا (Gift Sets)
        // ══════════════════════════════════════════════════════════════
        $created += $this->seedGiftSets($brands, $categories, $taxRate);

        // ══════════════════════════════════════════════════════════════
        // 6. مخلطات عطرية (Blends)
        // ══════════════════════════════════════════════════════════════
        $created += $this->seedBlends($brands, $categories, $taxRate, $sizes, $concs, $attrSize, $attrConc);

        // ══════════════════════════════════════════════════════════════
        // 7. عطور غربية (Western Perfumes)
        // ══════════════════════════════════════════════════════════════
        $created += $this->seedWesternPerfumes($brands, $categories, $taxRate, $sizes, $concs, $types, $attrSize, $attrConc, $attrType);

        // ── ملخص ──
        $totalVariants = ProductVariant::count();
        $totalImages   = ProductImage::count();
        $this->command->info('');
        $this->command->info('==============================================');
        $this->command->info("  ✅ Products Seeded Successfully!");
        $this->command->info("     • Products:  {$created}");
        $this->command->info("     • Variants:  {$totalVariants}");
        $this->command->info("     • Images:    {$totalImages}");
        $this->command->info('==============================================');
    }

    // ── مساعد: إنشاء منتج ──
    protected function makeProduct(array $data, array $catIds, array $attributeSync): Product
    {
        $p = Product::create($data);
        $filtered = array_filter($catIds);
        if (!empty($filtered)) {
            $p->categories()->sync($filtered);
        }
        if (!empty($attributeSync)) {
            $p->attributes()->sync($attributeSync);
        }
        // صورة رئيسية
        ProductImage::create([
            'product_id'    => $p->id,
            'variant_id'    => null,
            'image_url'     => $data['main_image'],
            'alt_text'      => $data['name_ar'],
            'display_order' => 0,
            'is_main'       => true,
        ]);
        return $p;
    }

    // ── مساعد: إنشاء متغير ──
    protected function makeVariant(Product $product, string $sku, float $price, float $cost, int $stock, array $valueIds, string $imgUrl, string $altText, bool $isMain): ProductVariant
    {
        $v = ProductVariant::create([
            'product_id'     => $product->id,
            'sku'            => $sku,
            'regular_price'  => $price,
            'sale_price'     => null,
            'cost_price'     => $cost,
            'stock_quantity' => $stock,
            'barcode'        => null,
            'is_active'      => true,
        ]);
        if (!empty($valueIds)) {
            $v->attributeValues()->sync($valueIds);
        }
        ProductImage::create([
            'product_id'    => $product->id,
            'variant_id'    => $v->id,
            'image_url'     => $imgUrl,
            'alt_text'      => $altText,
            'display_order' => 1,
            'is_main'       => $isMain,
        ]);
        return $v;
    }

    // ═══════════════════════════════════════════════════════════════
    // 1. دهن عود
    // ═══════════════════════════════════════════════════════════════
    protected function seedOudOils($brands, $categories, $taxRate, $sizes, $concs, $oudTypes, $attrSize, $attrConc, $attrOud): int
    {
        $items = [
            ['oud-khmer','دهن عود كمبودي','Cambodian Oud Oil','arabian-oud','كمبودي',149,289,549,true],
            ['oud-hindi','دهن عود هندي','Hindi Oud Oil','abdul-samad-al-qurashi','هندي',199,379,719,true],
            ['oud-malaysian','دهن عود ماليزي','Malaysian Oud Oil','al-majed-oud','ماليزي',179,339,649,false],
            ['oud-indonesian','دهن عود إندونيسي','Indonesian Oud Oil','ajmal','إندونيسي',129,249,479,false],
            ['oud-thai','دهن عود تايلاندي','Thai Oud Oil','rasasi','تايلاندي',159,299,569,true],
            ['oud-papuan','دهن عود بابوي','Papuan Oud Oil','khaloud','بابوي',219,419,799,false],
            ['oud-super','دهن عود سوبر (ممتاز)','Super Oud Oil (Premium)','house-of-oud','مخلط',249,479,919,true],
        ];
        $cat = $categories->get('oud-oils');
        $count = 0;
        $concOud = $concs->get('Pure Oud Oil');
        $concOudId = $concOud?->id;

        foreach ($items as [$slug, $nameAr, $nameEn, $brandSlug, $oudType, $p3, $p6, $p12, $featured]) {
            $brand = $brands->get($brandSlug);
            $oudVal = $oudTypes->get($oudType);
            $prefix = 'OIL-'.strtoupper(substr($slug, 4, 3));

            $p = $this->makeProduct(
                [
                    'name_ar'            => $nameAr,'name_en' => $nameEn,'slug' => $slug,
                    'description_ar'     => "دهن عود {$oudType} أصلي 100%، معتق لفترة طويلة.",
                    'description_en'     => "100% pure {$oudType} oud oil.",
                    'sku'                => $prefix.'-'.$brand?->id,
                    'regular_price'      => $p3, 'price_includes_tax' => true,
                    'cost_price'         => round($p3*0.4,2), 'tax_rate_id' => $taxRate?->id,
                    'quantity_in_stock'  => 500, 'low_stock_threshold' => 20, 'max_per_order' => 10,
                    'weight' => 0.05, 'dimensions' => '5x3x3 cm',
                    'main_image' => $this->imagePlaceholders['oil'],
                    'is_active' => true, 'is_featured' => $featured,
                    'brand_id' => $brand?->id,
                    'is_returnable' => false, 'is_exchangeable' => false,
                    'return_period_days' => 0, 'is_cancellable' => true,
                    'meta_title' => $nameAr.' - متجر العود',
                    'meta_description' => "اشتر {$nameAr} بأفضل سعر.",
                ],
                [$cat?->id],
                array_filter([
                    $attrSize?->id => ['is_variation' => true, 'display_order' => 1],
                    $attrConc?->id => ['is_variation' => false,'display_order' => 2],
                    $attrOud?->id  => ['is_variation' => false,'display_order' => 3],
                ])
            );

            foreach ([['3 ml',$p3],['6 ml',$p6],['12 ml',$p12]] as [$sizeKey,$price]) {
                $sz = $sizes->get($sizeKey);
                if (!$sz) continue;
                $this->makeVariant(
                    $p,
                    $prefix.'-'.str_replace(' ','',$sizeKey),
                    (float)$price, round($price*0.4,2), 100,
                    array_filter([$sz->id, $concOudId, $oudVal?->id]),
                    $this->imagePlaceholders['oil'],
                    "{$nameAr} - {$sizeKey}",
                    $sizeKey === '3 ml'
                );
            }
            $count++;
            $this->command->info("  ✓ Oud Oil: {$nameAr}");
        }
        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    // 2. عطور شرقية
    // ═══════════════════════════════════════════════════════════════
    protected function seedArabicPerfumes($brands, $categories, $taxRate, $sizes, $concs, $types, $attrSize, $attrConc, $attrType): int
    {
        $items = [
            ['musk-tabriz','مسك الطائف','Taif Musk','abdul-samad-al-qurashi','للجنسين',149,249,399,true],
            ['ward-al-madina','ورد المدينة','Rose of Madinah','arabian-oud','للجنسين',179,299,479,true],
            ['amber-royal','عنبر ملكي','Royal Amber','ajmal','للجنسين',199,349,549,true],
            ['khaltat-al-majed','مخلطة الماجد','Al Majed Blend','al-majed-oud','رجالي',249,429,699,true],
            ['rasasi-al-ghutra','الغترة - راس','Rasasi Al Ghutra','rasasi','رجالي',219,379,599,false],
            ['swiss-musk','مسك سويسري','Swiss Musk','swiss-arabian','للجنسين',129,219,349,false],
            ['khaloud-al-sultan','خلود السلطان','Khaloud Al Sultan','khaloud','رجالي',299,499,799,false],
        ];
        $cat1 = $categories->get('arabic-perfumes');
        $cat2 = $categories->get('attar');
        $count = 0;
        $concEdp = $concs->get('Eau de Parfum');
        $concEdpId = $concEdp?->id;

        foreach ($items as [$slug,$nameAr,$nameEn,$brandSlug,$gender,$p25,$p50,$p100,$featured]) {
            $brand = $brands->get($brandSlug);
            $genderVal = $types->get($gender);
            $prefix = strtoupper(substr(explode('-',$slug)[0],0,3));

            $p = $this->makeProduct(
                [
                    'name_ar'            => $nameAr,'name_en' => $nameEn,'slug' => $slug,
                    'description_ar'     => "عطر شرقي فاخر من {$nameAr}.",
                    'description_en'     => "Luxurious oriental perfume: {$nameEn}.",
                    'sku'                => 'PRF-'.$prefix.'-'.$brand?->id,
                    'regular_price'      => $p25, 'price_includes_tax' => true,
                    'cost_price'         => round($p25*0.35,2), 'tax_rate_id' => $taxRate?->id,
                    'quantity_in_stock'  => 300, 'low_stock_threshold' => 15, 'max_per_order' => 5,
                    'weight' => 0.15, 'dimensions' => '8x5x5 cm',
                    'main_image' => $this->imagePlaceholders['perfume'],
                    'is_active' => true, 'is_featured' => $featured,
                    'brand_id' => $brand?->id,
                    'is_returnable' => false, 'is_exchangeable' => true,
                    'return_period_days' => 7, 'is_cancellable' => true,
                    'meta_title' => $nameAr.' - عطور شرقية',
                    'meta_description' => "اشتر {$nameAr} بأفضل سعر.",
                ],
                [$cat1?->id, $cat2?->id],
                array_filter([
                    $attrSize?->id => ['is_variation' => true, 'display_order' => 1],
                    $attrConc?->id => ['is_variation' => false,'display_order' => 2],
                    $attrType?->id => ['is_variation' => false,'display_order' => 3],
                ])
            );

            foreach ([['25 ml',$p25],['50 ml',$p50],['100 ml',$p100]] as [$sizeKey,$price]) {
                $sz = $sizes->get($sizeKey);
                if (!$sz) continue;
                $this->makeVariant(
                    $p,
                    $prefix.'-'.str_replace(' ','',$sizeKey).'-'.$brand?->id,
                    (float)$price, round($price*0.35,2), 50,
                    array_filter([$sz->id, $concEdpId, $genderVal?->id]),
                    $this->imagePlaceholders['perfume'],
                    "{$nameAr} - {$sizeKey}",
                    $sizeKey === '25 ml'
                );
            }
            $count++;
            $this->command->info("  ✓ Perfume: {$nameAr}");
        }
        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    // 3. بخور
    // ═══════════════════════════════════════════════════════════════
    protected function seedBakhour($brands, $categories, $taxRate, $sizes, $attrSize): int
    {
        $items = [
            ['bakhour-malaki','بخور ملكي','Royal Bakhour','abdul-samad-al-qurashi',89,159,349,true],
            ['bakhour-wardi','بخور وردي','Rose Bakhour','arabian-oud',69,129,279,false],
            ['bakhour-oud-majestic','بخور عود مهيب','Majestic Oud Bakhour','al-majed-oud',119,219,479,true],
            ['bakhour-turkish','بخور تركي فاخر','Turkish Luxury Bakhour','ajmal',79,149,329,false],
        ];
        $cat1 = $categories->get('incense');
        $cat2 = $categories->get('bakhour');
        $count = 0;

        foreach ($items as [$slug,$nameAr,$nameEn,$brandSlug,$p50,$p100,$p250,$featured]) {
            $brand = $brands->get($brandSlug);
            $prefix = 'BKH-'.strtoupper(substr(explode('-',$slug)[1]??'BKH',0,3));

            $p = $this->makeProduct(
                [
                    'name_ar' => $nameAr,'name_en' => $nameEn,'slug' => $slug,
                    'description_ar' => "بخور فاخر برائحة {$nameAr}.",
                    'description_en' => "Luxury bakhour: {$nameEn}.",
                    'sku' => $prefix.'-'.$brand?->id,
                    'regular_price' => $p50, 'price_includes_tax' => true,
                    'cost_price' => round($p50*0.3,2), 'tax_rate_id' => $taxRate?->id,
                    'quantity_in_stock' => 200, 'low_stock_threshold' => 10, 'max_per_order' => 20,
                    'weight' => 0.25, 'dimensions' => '10x10x5 cm',
                    'main_image' => $this->imagePlaceholders['bakhour'],
                    'is_active' => true, 'is_featured' => $featured,
                    'brand_id' => $brand?->id,
                    'is_returnable' => false, 'is_exchangeable' => true,
                    'return_period_days' => 3, 'is_cancellable' => true,
                    'meta_title' => $nameAr.' - أفضل البخور',
                    'meta_description' => "اشتر {$nameAr}.",
                ],
                [$cat1?->id, $cat2?->id],
                $attrSize ? [$attrSize->id => ['is_variation' => true, 'display_order' => 1]] : []
            );

            foreach ([['50 g',$p50],['100 g',$p100],['250 g',$p250]] as [$sizeKey,$price]) {
                $sz = $sizes->get($sizeKey);
                if (!$sz) continue;
                $this->makeVariant(
                    $p,
                    $prefix.'-'.str_replace(' ','',$sizeKey),
                    (float)$price, round($price*0.3,2), 30,
                    [$sz->id],
                    $this->imagePlaceholders['bakhour'],
                    "{$nameAr} - {$sizeKey}",
                    $sizeKey === '50 g'
                );
            }
            $count++;
            $this->command->info("  ✓ Bakhour: {$nameAr}");
        }
        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    // 4. رقائق عود
    // ═══════════════════════════════════════════════════════════════
    protected function seedOudChips($brands, $categories, $taxRate, $sizes, $oudTypes, $attrSize, $attrOud): int
    {
        $items = [
            ['oud-chips-khmer','رقائق عود كمبودي','Cambodian Oud Chips','arabian-oud','كمبودي',79,149,349,true],
            ['oud-chips-hindi','رقائق عود هندي','Hindi Oud Chips','abdul-samad-al-qurashi','هندي',99,189,449,true],
            ['oud-chips-mix','مخلوط رقائق عود','Mixed Oud Chips','al-majed-oud','مخلط',59,109,259,false],
        ];
        $cat1 = $categories->get('oud');
        $cat2 = $categories->get('oud-chips');
        $count = 0;

        foreach ($items as [$slug,$nameAr,$nameEn,$brandSlug,$oudType,$p20,$p50,$p100,$featured]) {
            $brand = $brands->get($brandSlug);
            $oudVal = $oudTypes->get($oudType);
            $prefix = 'CHP-'.strtoupper(substr(explode('-',$slug)[2]??'CHP',0,3));

            $p = $this->makeProduct(
                [
                    'name_ar' => $nameAr,'name_en' => $nameEn,'slug' => $slug,
                    'description_ar' => "رقائق عود طبيعي {$oudType} درجة أولى.",
                    'description_en' => "Grade A natural {$oudType} oud chips.",
                    'sku' => $prefix.'-'.$brand?->id,
                    'regular_price' => $p20, 'price_includes_tax' => true,
                    'cost_price' => round($p20*0.35,2), 'tax_rate_id' => $taxRate?->id,
                    'quantity_in_stock' => 400, 'low_stock_threshold' => 20, 'max_per_order' => 10,
                    'weight' => 0.1, 'dimensions' => '15x10x2 cm',
                    'main_image' => $this->imagePlaceholders['oud'],
                    'is_active' => true, 'is_featured' => $featured,
                    'brand_id' => $brand?->id,
                    'is_returnable' => false, 'is_exchangeable' => false,
                    'return_period_days' => 0, 'is_cancellable' => true,
                    'meta_title' => $nameAr.' - عود طبيعي',
                    'meta_description' => "اشتر {$nameAr}.",
                ],
                [$cat1?->id, $cat2?->id],
                array_filter([
                    $attrSize?->id => ['is_variation' => true, 'display_order' => 1],
                    $attrOud?->id  => ['is_variation' => false,'display_order' => 2],
                ])
            );

            foreach ([['20 g',$p20],['50 g',$p50],['100 g',$p100]] as [$sizeKey,$price]) {
                $sz = $sizes->get($sizeKey);
                if (!$sz) continue;
                $this->makeVariant(
                    $p,
                    $prefix.'-'.str_replace(' ','',$sizeKey),
                    (float)$price, round($price*0.35,2), 50,
                    array_filter([$sz->id, $oudVal?->id]),
                    $this->imagePlaceholders['oud'],
                    "{$nameAr} - {$sizeKey}",
                    $sizeKey === '20 g'
                );
            }
            $count++;
            $this->command->info("  ✓ Oud Chips: {$nameAr}");
        }
        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    // 5. أطقم هدايا
    // ═══════════════════════════════════════════════════════════════
    protected function seedGiftSets($brands, $categories, $taxRate): int
    {
        $items = [
            ['gift-luxury-oud','طقم هدايا العود الفاخر','Luxury Oud Gift Set','arabian-oud',399,true],
            ['gift-perfume-collection','طقم هدايا تشكيلة عطور','Perfume Collection Gift Set','ajmal',599,true],
            ['gift-bakhour-deluxe','طقم هدايا بخور فاخر','Deluxe Bakhour Gift Set','abdul-samad-al-qurashi',449,true],
            ['gift-complete','الطقم الشامل','Complete Gift Set','al-majed-oud',799,false],
        ];
        $cat1 = $categories->get('gifts');
        $cat2 = $categories->get('gift-sets');
        $count = 0;

        foreach ($items as [$slug,$nameAr,$nameEn,$brandSlug,$price,$featured]) {
            $brand = $brands->get($brandSlug);
            $prefix = 'GFT-'.strtoupper(substr(explode('-',$slug)[1]??'GFT',0,3));

            $p = $this->makeProduct(
                [
                    'name_ar' => $nameAr,'name_en' => $nameEn,'slug' => $slug,
                    'description_ar' => "{$nameAr} – هدية فاخرة لمن تحب.",
                    'description_en' => "{$nameEn} – A luxurious gift for your loved ones.",
                    'sku' => $prefix.'-'.$brand?->id,
                    'regular_price' => $price, 'price_includes_tax' => true,
                    'cost_price' => round($price*0.5,2), 'tax_rate_id' => $taxRate?->id,
                    'quantity_in_stock' => 100, 'low_stock_threshold' => 5, 'max_per_order' => 3,
                    'weight' => 0.5, 'dimensions' => '25x20x10 cm',
                    'main_image' => $this->imagePlaceholders['gift'],
                    'is_active' => true, 'is_featured' => $featured,
                    'brand_id' => $brand?->id,
                    'is_returnable' => false, 'is_exchangeable' => true,
                    'return_period_days' => 3, 'is_cancellable' => true,
                    'meta_title' => $nameAr.' - هدايا فاخرة',
                    'meta_description' => "اشتر {$nameAr} كهدية مميزة.",
                ],
                [$cat1?->id, $cat2?->id],
                [] // لا سمات لأطقم الهدايا
            );

            // صورة إضافية
            ProductImage::create([
                'product_id' => $p->id, 'variant_id' => null,
                'image_url'  => $this->imagePlaceholders['perfume'],
                'alt_text'   => $nameAr.' - منظر آخر',
                'display_order' => 1, 'is_main' => false,
            ]);

            $count++;
            $this->command->info("  ✓ Gift Set: {$nameAr}");
        }
        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    // 6. مخلطات عطرية
    // ═══════════════════════════════════════════════════════════════
    protected function seedBlends($brands, $categories, $taxRate, $sizes, $concs, $attrSize, $attrConc): int
    {
        $items = [
            ['blend-oud-rose','مخلط عود وورد','Oud & Rose Blend','swiss-arabian',179,299,499,true],
            ['blend-amber-musk','مخلط عنبر ومسك','Amber & Musk Blend','rasasi',139,249,419,false],
        ];
        $cat1 = $categories->get('blends');
        $cat2 = $categories->get('perfumes');
        $count = 0;
        $concExtrait = $concs->get('Extrait de Parfum');
        $concExtraitId = $concExtrait?->id;

        foreach ($items as [$slug,$nameAr,$nameEn,$brandSlug,$p25,$p50,$p100,$featured]) {
            $brand = $brands->get($brandSlug);
            $prefix = 'BLD-'.strtoupper(substr(explode('-',$slug)[1]??'BLD',0,3));

            $p = $this->makeProduct(
                [
                    'name_ar' => $nameAr,'name_en' => $nameEn,'slug' => $slug,
                    'description_ar' => "مخلط عطري فاخر: {$nameAr}.",
                    'description_en' => "Luxury fragrance blend: {$nameEn}.",
                    'sku' => $prefix.'-'.$brand?->id,
                    'regular_price' => $p25, 'price_includes_tax' => true,
                    'cost_price' => round($p25*0.4,2), 'tax_rate_id' => $taxRate?->id,
                    'quantity_in_stock' => 150, 'low_stock_threshold' => 10, 'max_per_order' => 5,
                    'weight' => 0.12, 'dimensions' => '7x5x5 cm',
                    'main_image' => $this->imagePlaceholders['blend'],
                    'is_active' => true, 'is_featured' => $featured,
                    'brand_id' => $brand?->id,
                    'is_returnable' => false, 'is_exchangeable' => true,
                    'return_period_days' => 7, 'is_cancellable' => true,
                    'meta_title' => $nameAr.' - مخلطات عطرية',
                    'meta_description' => "اشتر {$nameAr}.",
                ],
                [$cat1?->id, $cat2?->id],
                array_filter([
                    $attrSize?->id => ['is_variation' => true, 'display_order' => 1],
                    $attrConc?->id => ['is_variation' => false,'display_order' => 2],
                ])
            );

            foreach ([['25 ml',$p25],['50 ml',$p50],['100 ml',$p100]] as [$sizeKey,$price]) {
                $sz = $sizes->get($sizeKey);
                if (!$sz) continue;
                $this->makeVariant(
                    $p,
                    $prefix.'-'.str_replace(' ','',$sizeKey),
                    (float)$price, round($price*0.4,2), 30,
                    array_filter([$sz->id, $concExtraitId]),
                    $this->imagePlaceholders['blend'],
                    "{$nameAr} - {$sizeKey}",
                    $sizeKey === '25 ml'
                );
            }
            $count++;
            $this->command->info("  ✓ Blend: {$nameAr}");
        }
        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    // 7. عطور غربية
    // ═══════════════════════════════════════════════════════════════
    protected function seedWesternPerfumes($brands, $categories, $taxRate, $sizes, $concs, $types, $attrSize, $attrConc, $attrType): int
    {
        $items = [
            ['western-oud-silk','حرير العود','Oud Silk','swiss-arabian','للجنسين',249,429,649,true],
            ['western-night-oud','ليل العود','Night Oud','rasasi','رجالي',319,529,799,false],
        ];
        $cat1 = $categories->get('western-perfumes');
        $cat2 = $categories->get('perfumes');
        $count = 0;
        $concEdp = $concs->get('Eau de Parfum');
        $concEdpId = $concEdp?->id;

        foreach ($items as [$slug,$nameAr,$nameEn,$brandSlug,$gender,$p30,$p50,$p100,$featured]) {
            $brand = $brands->get($brandSlug);
            $genderVal = $types->get($gender);
            $prefix = 'WST-'.strtoupper(substr(explode('-',$slug)[1]??'WST',0,3));

            $p = $this->makeProduct(
                [
                    'name_ar' => $nameAr,'name_en' => $nameEn,'slug' => $slug,
                    'description_ar' => "عطر غربي فاخر مع لمسات عربية: {$nameAr}.",
                    'description_en' => "Luxury western perfume with Arabic essence: {$nameEn}.",
                    'sku' => $prefix.'-'.$brand?->id,
                    'regular_price' => $p30, 'price_includes_tax' => true,
                    'cost_price' => round($p30*0.3,2), 'tax_rate_id' => $taxRate?->id,
                    'quantity_in_stock' => 200, 'low_stock_threshold' => 10, 'max_per_order' => 3,
                    'weight' => 0.2, 'dimensions' => '10x6x6 cm',
                    'main_image' => $this->imagePlaceholders['perfume'],
                    'is_active' => true, 'is_featured' => $featured,
                    'brand_id' => $brand?->id,
                    'is_returnable' => false, 'is_exchangeable' => true,
                    'return_period_days' => 7, 'is_cancellable' => true,
                    'meta_title' => $nameAr.' - عطور غربية',
                    'meta_description' => "اشتر {$nameAr}.",
                ],
                [$cat1?->id, $cat2?->id],
                array_filter([
                    $attrSize?->id => ['is_variation' => true, 'display_order' => 1],
                    $attrConc?->id => ['is_variation' => false,'display_order' => 2],
                    $attrType?->id => ['is_variation' => false,'display_order' => 3],
                ])
            );

            foreach ([['30 ml',$p30],['50 ml',$p50],['100 ml',$p100]] as [$sizeKey,$price]) {
                $sz = $sizes->get($sizeKey);
                if (!$sz) continue;
                $this->makeVariant(
                    $p,
                    $prefix.'-'.str_replace(' ','',$sizeKey),
                    (float)$price, round($price*0.3,2), 40,
                    array_filter([$sz->id, $concEdpId, $genderVal?->id]),
                    $this->imagePlaceholders['perfume'],
                    "{$nameAr} - {$sizeKey}",
                    $sizeKey === '30 ml'
                );
            }
            $count++;
            $this->command->info("  ✓ Western Perfume: {$nameAr}");
        }
        return $count;
    }
}
