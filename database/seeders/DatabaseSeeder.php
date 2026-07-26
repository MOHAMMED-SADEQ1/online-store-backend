<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 1. أساسيات النظام (System Essentials)
        // ═══════════════════════════════════════════════════════════
        $this->call([
            AdminUserSeeder::class,      // مستخدم المشرف الافتراضي
            PaymentMethodSeeder::class,  // طرق الدفع (ميسر + COD)
            TaxRateSeeder::class,        // ضريبة القيمة المضافة 15%
        ]);

        // ═══════════════════════════════════════════════════════════
        // 2. هيكل المتجر (Store Structure)
        // ═══════════════════════════════════════════════════════════
        $this->call([
            BrandSeeder::class,          // العلامات التجارية للعود والعطور
            CategorySeeder::class,       // الفئات الهرمية
            AttributeSeeder::class,      // سمات المنتجات (حجم، تركيز...)
        ]);

        // ═══════════════════════════════════════════════════════════
        // 3. الشحن (Shipping)
        // ═══════════════════════════════════════════════════════════
        $this->call([
            ShippingZoneSeeder::class,   // مناطق الشحن
            ShippingCitySeeder::class,   // المدن السعودية
        ]);

        // ═══════════════════════════════════════════════════════════
        // 4. منتجات العود والعطور (Oud & Perfume Products)
        // ═══════════════════════════════════════════════════════════
        $this->call([
            EavDemoSeeder::class,        // بيانات تجريبية (اختياري)
            LoyaltyTierSeeder::class,    // مستويات برنامج الولاء
            ProductSeeder::class,        // المنتجات والمتغيرات والصور
        ]);
    }
}
