<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingCity extends Model
{
    protected $fillable = [
        'shipping_zone_id', 'name_ar', 'name_en', 'cost',
        'estimated_days_min', 'estimated_days_max',
        'free_shipping_threshold', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function shippingZone()
    {
        return $this->belongsTo(ShippingZone::class);
    }
}
