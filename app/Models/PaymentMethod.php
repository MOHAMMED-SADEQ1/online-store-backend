<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    protected $fillable = ['name_ar', 'name_en', 'gateway', 'is_online', 'is_active', 'additional_fee'];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'is_active' => 'boolean',
            'additional_fee' => 'decimal:2',
        ];
    }
}
