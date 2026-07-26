<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCardUsage extends Model
{
    protected $table = 'gift_card_usages';

    protected $fillable = [
        'gift_card_id', 'order_id', 'user_id',
        'amount_used', 'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'amount_used'  => 'decimal:2',
            'balance_after'=> 'decimal:2',
        ];
    }

    public function giftCard()
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
