<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    protected $fillable = [
        'user_id', 'code', 'total_referred', 'total_earned', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_referred' => 'integer',
            'total_earned'   => 'decimal:2',
            'is_active'      => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function redemptions()
    {
        return $this->hasMany(ReferralRedemption::class);
    }

    /**
     * Generate a unique referral code for a user.
     */
    public static function generateForUser(User $user): self
    {
        $base = strtolower(substr($user->username ?: $user->first_name ?: 'user', 0, 6));
        $code = $base . rand(100, 999);

        while (static::where('code', $code)->exists()) {
            $code = $base . rand(100, 999);
        }

        return static::create([
            'user_id' => $user->id,
            'code'    => $code,
        ]);
    }
}
