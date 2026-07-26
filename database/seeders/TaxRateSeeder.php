<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        TaxRate::firstOrCreate(
            ['rate_percent' => 15.00],
            [
                'name_ar'   => 'ضريبة القيمة المضافة (15%)',
                'name_en'   => 'VAT (15%)',
                'is_active' => true,
            ]
        );

        $this->command->info('Tax rate seeded: VAT 15%.');
    }
}
