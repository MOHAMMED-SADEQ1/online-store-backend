<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    protected $fillable = [
        'name_ar', 'name_en', 'slug', 'min_points', 'max_points',
        'points_multiplier', 'discount_percent', 'free_shipping',
        'priority_support', 'is_active', 'badge',
    ];

    protected function casts(): array
    {
        return [
            'min_points'       => 'integer',
            'max_points'       => 'integer',
            'points_multiplier'=> 'decimal:2',
            'discount_percent' => 'decimal:2',
            'free_shipping'    => 'boolean',
            'priority_support' => 'boolean',
            'is_active'        => 'boolean',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('min_points');
    }

    /**
     * Find the appropriate tier for given points.
     */
    public static function findTierForPoints(int $points): ?self
    {
        return static::active()
            ->where('min_points', '<=', $points)
            ->where(fn($q) => $q->whereNull('max_points')->orWhere('max_points', '>=', $points))
            ->first();
    }
}
