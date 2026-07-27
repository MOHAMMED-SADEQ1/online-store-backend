<?php

namespace Database\Seeders;

use App\Models\FlashSale;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FlashSaleSeeder extends Seeder
{
    public function run(): void
    {
        // ── نحتاج منتجات موجودة مسبقاً ──
        $products = Product::whereIn('slug', [
            'oud-super', 'oud-khmer', 'musk-tabriz',
            'bakhour-malaki', 'gift-luxury-oud',
        ])->get()->keyBy('slug');

        $variants = ProductVariant::whereIn('sku', [
            'SUP-3ml', 'KHM-12ml', 'MUS-50ml',
        ])->get()->keyBy('sku');

        $now = Carbon::now();

        $sales = [];

        // 1. تخفيض على دهن عود سوبر - متغير 3 مل
        if (isset($products['oud-super'])) {
            $sales[] = [
                'title_ar'      => '🔥 تخفيض على دهن عود سوبر',
                'title_en'      => '🔥 Super Oud Oil Flash Sale',
                'product_id'    => $products['oud-super']->id,
                'variant_id'    => $variants['SUP-3ml']->id ?? null,
                'flash_price'   => 199.00,
                'max_quantity'  => 30,
                'sold_quantity' => 12,
                'start_date'    => $now->copy()->subDay(),
                'end_date'      => $now->copy()->addDays(3),
                'is_active'     => true,
            ];
        }

        // 2. تخفيض على دهن عود كمبودي - متغير 12 مل
        if (isset($products['oud-khmer'])) {
            $sales[] = [
                'title_ar'      => '🔥 عرض خاص: دهن عود كمبودي',
                'title_en'      => '🔥 Special: Cambodian Oud Oil',
                'product_id'    => $products['oud-khmer']->id,
                'variant_id'    => $variants['KHM-12ml']->id ?? null,
                'flash_price'   => 449.00,
                'max_quantity'  => 20,
                'sold_quantity' => 8,
                'start_date'    => $now->copy()->subHours(6),
                'end_date'      => $now->copy()->addDays(5),
                'is_active'     => true,
            ];
        }

        // 3. تخفيض على مسك الطائف - متغير 50 مل
        if (isset($products['musk-tabriz'])) {
            $sales[] = [
                'title_ar'      => '🔥 مسك الطائف بخصم 30%',
                'title_en'      => '🔥 Taif Musk 30% OFF',
                'product_id'    => $products['musk-tabriz']->id,
                'variant_id'    => $variants['MUS-50ml']->id ?? null,
                'flash_price'   => 179.00,
                'max_quantity'  => 25,
                'sold_quantity' => 15,
                'start_date'    => $now->copy()->subHours(12),
                'end_date'      => $now->copy()->addDays(2),
                'is_active'     => true,
            ];
        }

        // 4. تخفيض على بخور ملكي (بدون متغير)
        if (isset($products['bakhour-malaki'])) {
            $sales[] = [
                'title_ar'      => '🔥 بخور ملكي - خصم 25%',
                'title_en'      => '🔥 Royal Bakhour - 25% OFF',
                'product_id'    => $products['bakhour-malaki']->id,
                'variant_id'    => null,
                'flash_price'   => 119.00,
                'max_quantity'  => 40,
                'sold_quantity' => 22,
                'start_date'    => $now->copy()->subDays(2),
                'end_date'      => $now->copy()->addDays(7),
                'is_active'     => true,
            ];
        }

        // 5. تخفيض على طقم هدايا
        if (isset($products['gift-luxury-oud'])) {
            $sales[] = [
                'title_ar'      => '🔥 طقم هدايا العود الفاخر',
                'title_en'      => '🔥 Luxury Oud Gift Set',
                'product_id'    => $products['gift-luxury-oud']->id,
                'variant_id'    => null,
                'flash_price'   => 299.00,
                'max_quantity'  => 15,
                'sold_quantity' => 5,
                'start_date'    => $now->copy()->subDay(),
                'end_date'      => $now->copy()->addDays(4),
                'is_active'     => true,
            ];
        }

        foreach ($sales as $sale) {
            FlashSale::firstOrCreate(
                [
                    'product_id' => $sale['product_id'],
                    'variant_id' => $sale['variant_id'],
                    'start_date' => $sale['start_date'],
                ],
                $sale
            );
        }

        $this->command->info('Flash sales seeded: ' . count($sales) . ' active flash sales created.');
    }
}
