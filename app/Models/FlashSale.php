<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    protected $fillable = [
        'title_ar', 'title_en', 'product_id', 'variant_id',
        'flash_price', 'max_quantity', 'sold_quantity',
        'start_date', 'end_date', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'flash_price'   => 'decimal:2',
            'max_quantity'  => 'integer',
            'sold_quantity' => 'integer',
            'start_date'    => 'datetime',
            'end_date'      => 'datetime',
            'is_active'     => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
}
