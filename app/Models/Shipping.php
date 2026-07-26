<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $table = 'shipping';

    protected $fillable = [
        'order_id', 'shipping_method', 'tracking_number', 'tracking_url',
        'carrier', 'shipping_zone_id', 'shipping_date', 'estimated_delivery',
        'actual_delivery', 'shipping_status',
    ];

    protected function casts(): array
    {
        return [
            'shipping_date' => 'datetime',
            'estimated_delivery' => 'datetime',
            'actual_delivery' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shippingZone()
    {
        return $this->belongsTo(ShippingZone::class);
    }
}
