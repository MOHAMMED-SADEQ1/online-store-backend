<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'attribute_type', 'display_order', 'is_global'];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attributes')
            ->withPivot('is_variation', 'display_order');
    }
}
