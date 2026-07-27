<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // طرق الدفع المتاحة في المتجر
        // ============================================================

        // 1. فيزا (Visa) – عبر بوابة ميسر
        PaymentMethod::firstOrCreate(
            ['gateway' => 'moyasar', 'name_en' => 'Visa'],
            [
                'name_ar'        => 'فيزا',
                'name_en'        => 'Visa',
                'gateway'        => 'moyasar',
                'is_online'      => true,
                'is_active'      => true,
                'additional_fee' => 0.00,
            ]
        );

        // 2. ماستركارد (Mastercard) – عبر بوابة ميسر
        PaymentMethod::firstOrCreate(
            ['gateway' => 'moyasar', 'name_en' => 'Mastercard'],
            [
                'name_ar'        => 'ماستركارد',
                'name_en'        => 'Mastercard',
                'gateway'        => 'moyasar',
                'is_online'      => true,
                'is_active'      => true,
                'additional_fee' => 0.00,
            ]
        );

        // 3. مدى (Mada) – شبكة المدفوعات السعودية – عبر بوابة ميسر
        PaymentMethod::firstOrCreate(
            ['gateway' => 'moyasar', 'name_en' => 'Mada'],
            [
                'name_ar'        => 'مدى',
                'name_en'        => 'Mada',
                'gateway'        => 'moyasar',
                'is_online'      => true,
                'is_active'      => true,
                'additional_fee' => 0.00,
            ]
        );

        // 4. أبل باي (Apple Pay) – عبر بوابة ميسر
        PaymentMethod::firstOrCreate(
            ['gateway' => 'moyasar', 'name_en' => 'Apple Pay'],
            [
                'name_ar'        => 'أبل باي',
                'name_en'        => 'Apple Pay',
                'gateway'        => 'moyasar',
                'is_online'      => true,
                'is_active'      => true,
                'additional_fee' => 0.00,
            ]
        );

        // 5. STC Pay (محفظة stc) – عبر بوابة ميسر
        PaymentMethod::firstOrCreate(
            ['gateway' => 'moyasar', 'name_en' => 'STC Pay'],
            [
                'name_ar'        => 'محفظة STC Pay',
                'name_en'        => 'STC Pay',
                'gateway'        => 'moyasar',
                'is_online'      => true,
                'is_active'      => true,
                'additional_fee' => 0.00,
            ]
        );

        // 6. الدفع عند الاستلام (COD) – كاش
        PaymentMethod::firstOrCreate(
            ['gateway' => 'cod'],
            [
                'name_ar'        => 'الدفع عند الاستلام (كاش)',
                'name_en'        => 'Cash on Delivery',
                'gateway'        => 'cod',
                'is_online'      => false,
                'is_active'      => true,
                'additional_fee' => 0.00,
            ]
        );

        $this->command->info('Payment methods seeded: 6 methods (Visa, Mastercard, Mada, Apple Pay, STC Pay, COD).');
    }
}
