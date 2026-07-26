<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompareItem extends Model
{
    protected $table = 'compare_items';

    protected $fillable = ['user_id', 'session_id', 'product_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
