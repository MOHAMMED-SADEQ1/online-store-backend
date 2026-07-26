<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $table = 'tax_rates';

    protected $fillable = ['name_ar', 'name_en', 'rate_percent', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rate_percent' => 'decimal:2',
        ];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
