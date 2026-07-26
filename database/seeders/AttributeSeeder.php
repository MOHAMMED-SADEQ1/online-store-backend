<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // 1. الحجم / الوزن (ml للسوائل + g للبخور والرقائق)
        // ============================================================
        $sizeWeight = Attribute::firstOrCreate(
            ['name_en' => 'Size'],
            [
                'name_ar'        => 'الحجم / الوزن',
                'attribute_type' => 'size',
                'display_order'  => 1,
                'is_global'      => true,
            ]
        );

        $values = [
            // أحجام السوائل (ml) – للعطور ودهن العود
            ['value_ar' => '3 مل',  'value_en' => '3 ml',  'display_order' => 1,  'extra_data' => ['unit' => 'ml', 'value' => 3]],
            ['value_ar' => '6 مل',  'value_en' => '6 ml',  'display_order' => 2,  'extra_data' => ['unit' => 'ml', 'value' => 6]],
            ['value_ar' => '12 مل', 'value_en' => '12 ml', 'display_order' => 3,  'extra_data' => ['unit' => 'ml', 'value' => 12]],
            ['value_ar' => '15 مل', 'value_en' => '15 ml', 'display_order' => 4,  'extra_data' => ['unit' => 'ml', 'value' => 15]],
            ['value_ar' => '20 مل', 'value_en' => '20 ml', 'display_order' => 5,  'extra_data' => ['unit' => 'ml', 'value' => 20]],
            ['value_ar' => '25 مل', 'value_en' => '25 ml', 'display_order' => 6,  'extra_data' => ['unit' => 'ml', 'value' => 25]],
            ['value_ar' => '30 مل', 'value_en' => '30 ml', 'display_order' => 7,  'extra_data' => ['unit' => 'ml', 'value' => 30]],
            ['value_ar' => '50 مل', 'value_en' => '50 ml', 'display_order' => 8,  'extra_data' => ['unit' => 'ml', 'value' => 50]],
            ['value_ar' => '75 مل', 'value_en' => '75 ml', 'display_order' => 9,  'extra_data' => ['unit' => 'ml', 'value' => 75]],
            ['value_ar' => '100 مل','value_en' => '100 ml','display_order' => 10, 'extra_data' => ['unit' => 'ml', 'value' => 100]],
            ['value_ar' => '150 مل','value_en' => '150 ml','display_order' => 11, 'extra_data' => ['unit' => 'ml', 'value' => 150]],

            // أوزان البخور والرقائق (g)
            ['value_ar' => '20 غ',  'value_en' => '20 g',  'display_order' => 12, 'extra_data' => ['unit' => 'g', 'value' => 20]],
            ['value_ar' => '50 غ',  'value_en' => '50 g',  'display_order' => 13, 'extra_data' => ['unit' => 'g', 'value' => 50]],
            ['value_ar' => '100 غ', 'value_en' => '100 g', 'display_order' => 14, 'extra_data' => ['unit' => 'g', 'value' => 100]],
            ['value_ar' => '250 غ', 'value_en' => '250 g', 'display_order' => 15, 'extra_data' => ['unit' => 'g', 'value' => 250]],
        ];

        foreach ($values as $val) {
            AttributeValue::firstOrCreate(
                ['attribute_id' => $sizeWeight->id, 'value_en' => $val['value_en']],
                $val
            );
        }

        // ============================================================
        // 2. تركيز العطر
        // ============================================================
        $concentration = Attribute::firstOrCreate(
            ['name_en' => 'Concentration'],
            [
                'name_ar'        => 'التركيز',
                'attribute_type' => 'select',
                'display_order'  => 2,
                'is_global'      => true,
            ]
        );

        $concentrations = [
            ['value_ar' => 'دهن عود خام',                'value_en' => 'Pure Oud Oil',     'display_order' => 1, 'extra_data' => ['concentration' => 'pure']],
            ['value_ar' => 'عطر مركز (Extrait)',         'value_en' => 'Extrait de Parfum','display_order' => 2, 'extra_data' => ['concentration' => 'extrait']],
            ['value_ar' => 'عطر (EDP)',                  'value_en' => 'Eau de Parfum',    'display_order' => 3, 'extra_data' => ['concentration' => 'edp']],
            ['value_ar' => 'عطر خفيف (EDT)',            'value_en' => 'Eau de Toilette',  'display_order' => 4, 'extra_data' => ['concentration' => 'edt']],
            ['value_ar' => 'عطر جوهر (EDC)',            'value_en' => 'Eau de Cologne',   'display_order' => 5, 'extra_data' => ['concentration' => 'edc']],
        ];

        foreach ($concentrations as $conc) {
            AttributeValue::firstOrCreate(
                ['attribute_id' => $concentration->id, 'value_en' => $conc['value_en']],
                $conc
            );
        }

        // ============================================================
        // 3. نوع العطر (رجالي / نسائي / للجنسين)
        // ============================================================
        $type = Attribute::firstOrCreate(
            ['name_en' => 'Type'],
            [
                'name_ar'        => 'النوع',
                'attribute_type' => 'select',
                'display_order'  => 3,
                'is_global'      => true,
            ]
        );

        $types = [
            ['value_ar' => 'رجالي',   'value_en' => 'Men',     'display_order' => 1],
            ['value_ar' => 'نسائي',   'value_en' => 'Women',   'display_order' => 2],
            ['value_ar' => 'للجنسين', 'value_en' => 'Unisex',  'display_order' => 3],
        ];

        foreach ($types as $t) {
            AttributeValue::firstOrCreate(
                ['attribute_id' => $type->id, 'value_en' => $t['value_en']],
                $t
            );
        }

        // ============================================================
        // 4. نوع العود (حسب المنشأ)
        // ============================================================
        $oudType = Attribute::firstOrCreate(
            ['name_en' => 'Oud Type'],
            [
                'name_ar'        => 'نوع العود',
                'attribute_type' => 'select',
                'display_order'  => 4,
                'is_global'      => true,
            ]
        );

        $oudTypes = [
            ['value_ar' => 'كمبودي',  'value_en' => 'Cambodian',  'display_order' => 1, 'extra_data' => ['origin' => 'cambodia']],
            ['value_ar' => 'هندي',    'value_en' => 'Indian',     'display_order' => 2, 'extra_data' => ['origin' => 'india']],
            ['value_ar' => 'ماليزي',  'value_en' => 'Malaysian',  'display_order' => 3, 'extra_data' => ['origin' => 'malaysia']],
            ['value_ar' => 'إندونيسي','value_en' => 'Indonesian', 'display_order' => 4, 'extra_data' => ['origin' => 'indonesia']],
            ['value_ar' => 'تايلاندي', 'value_en' => 'Thai',       'display_order' => 5, 'extra_data' => ['origin' => 'thailand']],
            ['value_ar' => 'بابوي',   'value_en' => 'Papuan',     'display_order' => 6, 'extra_data' => ['origin' => 'papua']],
            ['value_ar' => 'مخلط',    'value_en' => 'Blended',    'display_order' => 7],
        ];

        foreach ($oudTypes as $ot) {
            AttributeValue::firstOrCreate(
                ['attribute_id' => $oudType->id, 'value_en' => $ot['value_en']],
                $ot
            );
        }

        $this->command->info('Attributes seeded: Size/Weight, Concentration, Type, Oud Type with all values.');
    }
}
