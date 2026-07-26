<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'address_type', 'street_address', 'city', 'state',
        'postal_code', 'country', 'is_default', 'latitude', 'longitude',
        'building_number', 'floor_number', 'apartment_number', 'additional_directions',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
