<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $table = 'attribute_values';
    public $timestamps = false;

    protected $fillable = ['attribute_id', 'value_ar', 'value_en', 'extra_data', 'display_order'];

    protected function casts(): array
    {
        return [
            'extra_data' => 'array',
            'display_order' => 'integer',
        ];
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'variant_attribute_values');
    }
}
