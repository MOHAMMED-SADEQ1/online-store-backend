<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingCheckout extends Model
{
    protected $table = 'pending_checkouts';

    protected $fillable = [
        'user_id', 'cart_id', 'shipping_address_id', 'billing_address_id',
        'notes', 'payment_method_id', 'transaction_id', 'payment_url',
        'callback_url', 'status', 'cart_data', 'total_amount',
        'discount_amount', 'final_amount', 'coupon_code', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'cart_data'       => 'array',
            'total_amount'    => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_amount'    => 'decimal:2',
            'expires_at'      => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}

