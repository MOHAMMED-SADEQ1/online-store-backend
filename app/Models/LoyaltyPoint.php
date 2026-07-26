<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyPoint extends Model
{
    protected $fillable = [
        'user_id', 'balance', 'lifetime_earned', 'lifetime_spent',
        'tier_id', 'tier_assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'balance'          => 'integer',
            'lifetime_earned'  => 'integer',
            'lifetime_spent'   => 'integer',
            'tier_assigned_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tier()
    {
        return $this->belongsTo(LoyaltyTier::class);
    }

    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class, 'user_id', 'user_id');
    }
}
