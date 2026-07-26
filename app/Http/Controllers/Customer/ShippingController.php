<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ShippingCity;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function cities(): JsonResponse
    {
        $locale = app()->getLocale();

        $cities = ShippingCity::where('is_active', true)
            ->with('shippingZone')
            ->get()
            ->map(fn($c) => [
                'id'      => $c->id,
                'name'    => $c->{'name_' . $locale},
            ]);

        return response()->json(['cities' => $cities]);
    }

    public function calculate(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        $data = $request->validate([
            'city_id'      => 'required|exists:shipping_cities,id',
            'address_id'   => 'nullable|exists:addresses,id',
            'cart_subtotal'=> 'required|numeric|min:0',
        ]);

        $city = ShippingCity::with('shippingZone')->findOrFail($data['city_id']);

        $cost = (float) $city->cost;
        $freeThreshold = $city->free_shipping_threshold;

        if ($freeThreshold && $data['cart_subtotal'] >= $freeThreshold) {
            $cost = 0;
        }

        return response()->json([
            'cost'                => $cost,
            'estimated_days_min'  => $city->estimated_days_min,
            'estimated_days_max'  => $city->estimated_days_max,
            'provider'            => $city->shippingZone?->{'name_' . $locale} ?? __('shipping.standard'),
        ]);
    }
}
