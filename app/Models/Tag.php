<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'slug'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tags');
    }
}
