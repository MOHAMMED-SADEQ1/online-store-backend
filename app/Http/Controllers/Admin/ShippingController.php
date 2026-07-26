<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shippings = Shipping::with('order:id,order_number', 'shippingZone')
            ->when($request->order_id, fn($q, $v) => $q->where('order_id', $v))
            ->when($request->shipping_status, fn($q, $v) => $q->where('shipping_status', $v))
            ->when($request->carrier, fn($q, $v) => $q->where('carrier', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($shippings);
    }

    public function show(Shipping $shipping): JsonResponse
    {
        $shipping->load('order', 'shippingZone');

        return response()->json(['shipping' => $shipping]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id'          => 'required|exists:orders,id',
            'shipping_method'   => 'required|string|max:100',
            'tracking_number'   => 'nullable|string|max:100',
            'tracking_url'      => 'nullable|string|max:255',
            'carrier'           => 'nullable|string|max:100',
            'shipping_zone_id'  => 'nullable|exists:shipping_zones,id',
            'shipping_date'     => 'nullable|date',
            'estimated_delivery' => 'nullable|date',
            'actual_delivery'   => 'nullable|date',
            'shipping_status'   => 'sometimes|in:pending,shipped,in_transit,out_for_delivery,delivered',
        ]);

        $shipping = Shipping::create($data);

        return response()->json([
            'message'  => 'Shipping record created.',
            'shipping' => $shipping->load('order', 'shippingZone'),
        ], 201);
    }

    public function update(Request $request, Shipping $shipping): JsonResponse
    {
        $data = $request->validate([
            'tracking_number'   => 'nullable|string|max:100',
            'tracking_url'      => 'nullable|string|max:255',
            'carrier'           => 'nullable|string|max:100',
            'shipping_date'     => 'nullable|date',
            'estimated_delivery' => 'nullable|date',
            'actual_delivery'   => 'nullable|date',
            'shipping_status'   => 'sometimes|in:pending,shipped,in_transit,out_for_delivery,delivered',
        ]);

        $shipping->update($data);

        return response()->json([
            'message'  => 'Shipping updated.',
            'shipping' => $shipping->fresh()->load('order', 'shippingZone'),
        ]);
    }

    public function destroy(Shipping $shipping): JsonResponse
    {
        $shipping->delete();

        return response()->json(['message' => 'Shipping record deleted.']);
    }
}
