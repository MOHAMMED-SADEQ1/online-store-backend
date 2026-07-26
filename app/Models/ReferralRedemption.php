<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralRedemption extends Model
{
    protected $fillable = [
        'referral_code_id', 'referred_user_id', 'order_id',
        'reward_amount', 'status', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_amount'  => 'decimal:2',
            'completed_at'   => 'datetime',
        ];
    }

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
