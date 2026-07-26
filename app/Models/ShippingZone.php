<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $table = 'shipping_zones';

    protected $fillable = ['name_ar', 'name_en', 'shipping_cost', 'free_shipping_threshold', 'is_active'];

    protected function casts(): array
    {
        return [
            'shipping_cost' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
