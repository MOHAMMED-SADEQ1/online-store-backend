<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'type', 'source', 'points',
        'balance_after', 'description_ar', 'description_en',
        'expires_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'points'       => 'integer',
            'balance_after'=> 'integer',
            'expires_at'   => 'datetime',
            'meta'         => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
