<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        PaymentMethod::firstOrCreate(
            ['gateway' => 'moyasar'],
            [
                'name_ar'        => 'فيزا / ماستركارد (ميسر)',
                'name_en'        => 'Visa / Mastercard (Moyasar)',
                'gateway'        => 'moyasar',
                'is_online'      => true,
                'is_active'      => true,
                'additional_fee' => 0.00,
            ]
        );

        PaymentMethod::firstOrCreate(
            ['gateway' => 'cod'],
            [
                'name_ar'        => 'الدفع عند الاستلام',
                'name_en'        => 'Cash on Delivery',
                'gateway'        => 'cod',
                'is_online'      => false,
                'is_active'      => true,
                'additional_fee' => 0.00,
            ]
        );
    }
}
