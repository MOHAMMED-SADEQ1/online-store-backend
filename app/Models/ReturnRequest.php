<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'return_type', 'status',
        'refund_amount', 'exchange_items', 'exchange_order_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'exchange_items' => 'array',
            'refund_amount'  => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function exchangeOrder()
    {
        return $this->belongsTo(Order::class, 'exchange_order_id');
    }
}
