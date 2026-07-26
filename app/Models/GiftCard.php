<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    protected $fillable = [
        'code', 'original_balance', 'current_balance',
        'purchaser_user_id', 'recipient_email', 'recipient_name',
        'message', 'sent_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'original_balance' => 'decimal:2',
            'current_balance'  => 'decimal:2',
            'sent_at'          => 'datetime',
            'expires_at'       => 'datetime',
            'is_active'        => 'boolean',
        ];
    }

    public function purchaser()
    {
        return $this->belongsTo(User::class, 'purchaser_user_id');
    }

    public function usages()
    {
        return $this->hasMany(GiftCardUsage::class);
    }

    public function scopeValid($q)
    {
        return $q->where('is_active', true)
            ->where('current_balance', '>', 0)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * Generate a unique gift card code.
     */
    public static function generateCode(): string
    {
        $prefix = 'GIFT-';
        do {
            $code = $prefix . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
