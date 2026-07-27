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
            TaxRateSeeder::class,        // ضريبة القيمة المضافة 15%
            PaymentMethodSeeder::class,  // طرق الدفع (فيزا، ماستركارد، مدى، أبل باي، STC Pay، كاش)
        ]);

        // ═══════════════════════════════════════════════════════════
        // 2. هيكل المتجر (Store Structure)
        // ═══════════════════════════════════════════════════════════
        $this->call([
            BrandSeeder::class,          // 8 علامات تجارية للعود والعطور
            CategorySeeder::class,       // 12 فئة هرمية
            AttributeSeeder::class,      // 4 سمات (حجم، تركيز، نوع، نوع عود) + 26 قيمة
            TagSeeder::class,            // 18 وسماً للمنتجات
        ]);

        // ═══════════════════════════════════════════════════════════
        // 3. الشحن (Shipping)
        // ═══════════════════════════════════════════════════════════
        $this->call([
            ShippingZoneSeeder::class,   // 4 مناطق شحن
            ShippingCitySeeder::class,   // 22 مدينة سعودية
        ]);

        // ═══════════════════════════════════════════════════════════
        // 4. منتجات العود والعطور (Oud & Perfume Products)
        // ═══════════════════════════════════════════════════════════
        $this->call([
            EavDemoSeeder::class,        // بيانات EAV تجريبية
            LoyaltyTierSeeder::class,    // 5 مستويات ولاء
            ProductSeeder::class,        // 29 منتج + 96 متغير + 108 صورة
        ]);

        // ═══════════════════════════════════════════════════════════
        // 5. بيانات المتجر التسويقية (Store Demo Data)
        // ═══════════════════════════════════════════════════════════
        $this->call([
            SettingSeeder::class,        // إعدادات المتجر (34 إعداداً)
            CouponSeeder::class,         // 6 كوبونات ترويجية
            FlashSaleSeeder::class,      // 5 تخفيضات فورية
        ]);
    }
}
