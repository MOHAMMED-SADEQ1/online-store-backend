<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'product_images';
    public $timestamps = false;

    protected $fillable = ['product_id', 'variant_id', 'image_url', 'alt_text', 'display_order', 'is_main'];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'display_order' => 'integer',
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
}
