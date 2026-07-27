<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingCity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingCityController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'shipping_cities' => ShippingCity::with('shippingZone:id,name_ar,name_en')
                ->orderBy('name_ar')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipping_zone_id'       => 'required|integer|exists:shipping_zones,id',
            'name_ar'                => 'required|string|max:100',
            'name_en'                => 'required|string|max:100',
            'cost'                   => 'required|numeric|min:0',
            'estimated_days_min'     => 'nullable|integer|min:1',
            'estimated_days_max'     => 'nullable|integer|min:1|gte:estimated_days_min',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'is_active'              => 'boolean',
        ]);

        $city = ShippingCity::create($data);

        return response()->json([
            'message'        => 'Shipping city created successfully.',
            'shipping_city'  => $city->load('shippingZone:id,name_ar,name_en'),
        ], 201);
    }

    public function show(ShippingCity $shippingCity): JsonResponse
    {
        return response()->json([
            'shipping_city' => $shippingCity->load('shippingZone'),
        ]);
    }

    public function update(Request $request, ShippingCity $shippingCity): JsonResponse
    {
        $data = $request->validate([
            'shipping_zone_id'       => 'sometimes|integer|exists:shipping_zones,id',
            'name_ar'                => 'sometimes|string|max:100',
            'name_en'                => 'sometimes|string|max:100',
            'cost'                   => 'sometimes|numeric|min:0',
            'estimated_days_min'     => 'nullable|integer|min:1',
            'estimated_days_max'     => 'nullable|integer|min:1|gte:estimated_days_min',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'is_active'              => 'boolean',
        ]);

        $shippingCity->update($data);

        return response()->json([
            'message'       => 'Shipping city updated successfully.',
            'shipping_city' => $shippingCity->fresh()->load('shippingZone:id,name_ar,name_en'),
        ]);
    }

    public function destroy(ShippingCity $shippingCity): JsonResponse
    {
        $shippingCity->delete();

        return response()->json(['message' => 'Shipping city deleted successfully.']);
    }
}
