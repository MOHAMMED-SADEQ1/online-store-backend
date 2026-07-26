<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingZoneController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'shipping_zones' => ShippingZone::orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar'                => 'required|string|max:100',
            'name_en'                => 'required|string|max:100',
            'shipping_cost'          => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'is_active'              => 'boolean',
        ]);

        $zone = ShippingZone::create($data);

        return response()->json([
            'message'       => 'Shipping zone created successfully.',
            'shipping_zone' => $zone,
        ], 201);
    }

    public function update(Request $request, ShippingZone $shippingZone): JsonResponse
    {
        $data = $request->validate([
            'name_ar'                => 'sometimes|string|max:100',
            'name_en'                => 'sometimes|string|max:100',
            'shipping_cost'          => 'sometimes|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'is_active'              => 'boolean',
        ]);

        $shippingZone->update($data);

        return response()->json([
            'message'       => 'Shipping zone updated successfully.',
            'shipping_zone' => $shippingZone->fresh(),
        ]);
    }

    public function destroy(ShippingZone $shippingZone): JsonResponse
    {
        $shippingZone->delete();

        return response()->json(['message' => 'Shipping zone deleted successfully.']);
    }
}
