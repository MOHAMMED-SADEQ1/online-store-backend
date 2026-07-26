<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'minimum_order_amount',
        'maximum_discount', 'applicable_to', 'minimum_quantity',
        'exclude_sale_items', 'usage_limit', 'used_count',
        'start_date', 'end_date', 'is_active',
        'is_free_shipping', 'per_user_limit', 'user_id', 'min_orders_count',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'exclude_sale_items' => 'boolean',
            'is_active' => 'boolean',
            'is_free_shipping' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'coupon_categories');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'coupon_products');
    }

    public function usage()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
