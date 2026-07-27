<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── معلومات المتجر الأساسية ──
            ['key' => 'store_name_ar',        'value' => 'متجر العود الفاخر',              'group' => 'general'],
            ['key' => 'store_name_en',        'value' => 'Luxury Oud Store',               'group' => 'general'],
            ['key' => 'store_description_ar',  'value' => 'أجود أنواع العود والعطور الشرقية الفاخرة', 'group' => 'general'],
            ['key' => 'store_description_en',  'value' => 'The finest oud and luxury oriental perfumes', 'group' => 'general'],
            ['key' => 'store_email',           'value' => 'info@mohammedeng.com',          'group' => 'general'],
            ['key' => 'store_phone',           'value' => '+966500000000',                  'group' => 'general'],
            ['key' => 'store_whatsapp',        'value' => '+966500000000',                  'group' => 'general'],
            ['key' => 'store_address_ar',      'value' => 'الرياض، المملكة العربية السعودية', 'group' => 'general'],
            ['key' => 'store_address_en',      'value' => 'Riyadh, Saudi Arabia',           'group' => 'general'],
            ['key' => 'working_hours_ar',      'value' => 'السبت - الخميس: 9 صباحاً - 10 مساءً', 'group' => 'general'],
            ['key' => 'working_hours_en',      'value' => 'Sat - Thu: 9 AM - 10 PM',        'group' => 'general'],

            // ── التواصل الاجتماعي ──
            ['key' => 'social_instagram',      'value' => 'https://instagram.com/store',     'group' => 'social'],
            ['key' => 'social_twitter',        'value' => 'https://twitter.com/store',       'group' => 'social'],
            ['key' => 'social_facebook',       'value' => 'https://facebook.com/store',      'group' => 'social'],
            ['key' => 'social_snapchat',       'value' => 'https://snapchat.com/add/store',  'group' => 'social'],
            ['key' => 'social_tiktok',         'value' => 'https://tiktok.com/@store',       'group' => 'social'],
            ['key' => 'social_youtube',        'value' => 'https://youtube.com/@store',      'group' => 'social'],

            // ── سياسات المتجر ──
            ['key' => 'return_policy_ar',      'value' => 'يمكن إرجاع المنتجات خلال 14 يوم من تاريخ الاستلام، بشرط أن تكون في حالتها الأصلية والعبوة سليمة. لا يُقبل إرجاع منتجات العود والعطور المفتوحة لأسباب صحية.', 'group' => 'policy'],
            ['key' => 'return_policy_en',      'value' => 'Products can be returned within 14 days of receipt, provided they are in their original condition and packaging. Opened oud and perfume products cannot be returned for health reasons.', 'group' => 'policy'],
            ['key' => 'shipping_policy_ar',    'value' => 'الشحن مجاني للطلبات فوق 200 ريال. مدة التوصيل من 1-5 أيام عمل حسب المنطقة.', 'group' => 'policy'],
            ['key' => 'shipping_policy_en',    'value' => 'Free shipping for orders over 200 SAR. Delivery time is 1-5 business days depending on the region.', 'group' => 'policy'],
            ['key' => 'privacy_policy_ar',     'value' => 'نحن نحمي معلوماتك الشخصية ولا نشاركها مع أطراف ثالثة.', 'group' => 'policy'],
            ['key' => 'privacy_policy_en',     'value' => 'We protect your personal information and do not share it with third parties.', 'group' => 'policy'],
            ['key' => 'terms_ar',              'value' => 'باستخدام هذا المتجر، فإنك توافق على جميع الشروط والأحكام.', 'group' => 'policy'],
            ['key' => 'terms_en',              'value' => 'By using this store, you agree to all terms and conditions.', 'group' => 'policy'],
            ['key' => 'about_us_ar',           'value' => 'متجر العود الفاخر هو وجهتك الأولى لشراء أجود أنواع العود والعطور الشرقية. نوفر أفضل المنتجات من أشهر الماركات العالمية.', 'group' => 'policy'],
            ['key' => 'about_us_en',           'value' => 'Luxury Oud Store is your premier destination for the finest oud and oriental perfumes. We offer the best products from the world\'s most renowned brands.', 'group' => 'policy'],

            // ── إعدادات الدفع ──
            ['key' => 'currency',              'value' => 'SAR',                             'group' => 'payment'],
            ['key' => 'currency_symbol_ar',    'value' => 'ر.س',                            'group' => 'payment'],
            ['key' => 'currency_symbol_en',    'value' => 'SAR',                            'group' => 'payment'],
            ['key' => 'tax_rate',              'value' => '15',                             'group' => 'payment'],
            ['key' => 'tax_label_ar',          'value' => 'ضريبة القيمة المضافة 15%',       'group' => 'payment'],
            ['key' => 'tax_label_en',          'value' => 'VAT 15%',                        'group' => 'payment'],

            // ── إعدادات الشحن ──
            ['key' => 'free_shipping_threshold', 'value' => '200',                          'group' => 'shipping'],
            ['key' => 'default_shipping_cost',   'value' => '25',                           'group' => 'shipping'],
            ['key' => 'estimated_delivery_min',  'value' => '1',                            'group' => 'shipping'],
            ['key' => 'estimated_delivery_max',  'value' => '5',                            'group' => 'shipping'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Settings seeded: ' . count($settings) . ' settings created.');
    }
}
