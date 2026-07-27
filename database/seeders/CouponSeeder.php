<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $categories = Category::all()->keyBy('slug');
        $products = Product::whereIn('slug', [
            'oud-super', 'musk-tabriz', 'bakhour-malaki', 'gift-luxury-oud',
        ])->get()->keyBy('slug');

        // ============================================================
        // 1. كوبون خصم عام 10%
        // ============================================================
        $coupon1 = Coupon::firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'discount_type'      => 'percentage',
                'discount_value'     => 10.00,
                'minimum_order_amount' => 100.00,
                'maximum_discount'   => 50.00,
                'applicable_to'      => 'all',
                'exclude_sale_items' => false,
                'usage_limit'        => 100,
                'used_count'         => 25,
                'start_date'         => $now->copy()->subMonth(),
                'end_date'           => $now->copy()->addMonths(3),
                'is_active'          => true,
                'is_free_shipping'   => false,
                'per_user_limit'     => 1,
            ]
        );

        // ============================================================
        // 2. كوبون خصم مبلغ ثابت 50 ريال
        // ============================================================
        $coupon2 = Coupon::firstOrCreate(
            ['code' => 'SAVE50'],
            [
                'discount_type'      => 'fixed',
                'discount_value'     => 50.00,
                'minimum_order_amount' => 300.00,
                'maximum_discount'   => null,
                'applicable_to'      => 'all',
                'exclude_sale_items' => true,
                'usage_limit'        => 50,
                'used_count'         => 10,
                'start_date'         => $now->copy()->subWeek(),
                'end_date'           => $now->copy()->addMonth(),
                'is_active'          => true,
                'is_free_shipping'   => false,
                'per_user_limit'     => 2,
            ]
        );

        // ============================================================
        // 3. كوبون شحن مجاني
        // ============================================================
        Coupon::firstOrCreate(
            ['code' => 'FREESHIP'],
            [
                'discount_type'      => 'percentage',
                'discount_value'     => 0,
                'minimum_order_amount' => 150.00,
                'maximum_discount'   => null,
                'applicable_to'      => 'all',
                'exclude_sale_items' => false,
                'usage_limit'        => 200,
                'used_count'         => 45,
                'start_date'         => $now->copy()->subDays(15),
                'end_date'           => $now->copy()->addMonths(2),
                'is_active'          => true,
                'is_free_shipping'   => true,
                'per_user_limit'     => 3,
            ]
        );

        // ============================================================
        // 4. كوبون خصم على فئة العطور 15%
        // ============================================================
        if ($perfumeCat = $categories->get('perfumes')) {
            $coupon4 = Coupon::firstOrCreate(
                ['code' => 'PERFUME15'],
                [
                    'discount_type'      => 'percentage',
                    'discount_value'     => 15.00,
                    'minimum_order_amount' => 200.00,
                    'maximum_discount'   => 100.00,
                    'applicable_to'      => 'categories',
                    'exclude_sale_items' => false,
                    'usage_limit'        => 50,
                    'used_count'         => 5,
                    'start_date'         => $now->copy()->subDays(5),
                    'end_date'           => $now->copy()->addMonths(1),
                    'is_active'          => true,
                    'is_free_shipping'   => false,
                    'per_user_limit'     => 1,
                ]
            );
            $coupon4->categories()->sync([$perfumeCat->id]);
        }

        // ============================================================
        // 5. كوبون خصم على منتج معين - دهن عود سوبر
        // ============================================================
        if ($product = $products->get('oud-super')) {
            $coupon5 = Coupon::firstOrCreate(
                ['code' => 'SUPEROUD20'],
                [
                    'discount_type'      => 'percentage',
                    'discount_value'     => 20.00,
                    'minimum_order_amount' => 0,
                    'maximum_discount'   => 150.00,
                    'applicable_to'      => 'products',
                    'exclude_sale_items' => false,
                    'usage_limit'        => 30,
                    'used_count'         => 3,
                    'start_date'         => $now->copy()->subDays(2),
                    'end_date'           => $now->copy()->addWeeks(2),
                    'is_active'          => true,
                    'is_free_shipping'   => false,
                    'per_user_limit'     => 1,
                ]
            );
            $coupon5->products()->sync([$product->id]);
        }

        // ============================================================
        // 6. كوبون خصم للعملاء الجدد فقط (بعد 3 طلبات)
        // ============================================================
        Coupon::firstOrCreate(
            ['code' => 'LOYALTY20'],
            [
                'discount_type'      => 'percentage',
                'discount_value'     => 20.00,
                'minimum_order_amount' => 250.00,
                'maximum_discount'   => 80.00,
                'applicable_to'      => 'all',
                'exclude_sale_items' => false,
                'usage_limit'        => 20,
                'used_count'         => 0,
                'start_date'         => $now->copy()->subDay(),
                'end_date'           => $now->copy()->addMonths(6),
                'is_active'          => true,
                'is_free_shipping'   => false,
                'per_user_limit'     => 1,
                'min_orders_count'   => 3,
            ]
        );

        $this->command->info('Coupons seeded: 6 coupons created (WELCOME10, SAVE50, FREESHIP, PERFUME15, SUPEROUD20, LOYALTY20).');
    }
}
