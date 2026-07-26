<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'identifier',
        'identifier_type',
        'otp',
        'temp_token',
        'expires_at',
        'used_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'   => 'datetime',
            'used_at'      => 'datetime',
            'verified_at'  => 'datetime',
        ];
    }

    public function scopeValid($query, string $identifier, string $otp)
    {
        return $query
            ->where('identifier', $identifier)
            ->where('otp', $otp)
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }
}
