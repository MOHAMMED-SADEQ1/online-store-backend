<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'flash_sales' => FlashSale::with(['product:id,name_ar,name_en,slug', 'variant:id,sku,regular_price'])
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title_ar'      => 'required|string|max:255',
            'title_en'      => 'required|string|max:255',
            'product_id'    => 'required|integer|exists:products,id',
            'variant_id'    => 'nullable|integer|exists:product_variants,id',
            'flash_price'   => 'required|numeric|min:0',
            'max_quantity'  => 'required|integer|min:1',
            'sold_quantity' => 'integer|min:0',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
            'is_active'     => 'boolean',
        ]);

        $flashSale = FlashSale::create($data);

        return response()->json([
            'message'    => 'Flash sale created successfully.',
            'flash_sale' => $flashSale->load(['product:id,name_ar,name_en,slug', 'variant:id,sku,regular_price']),
        ], 201);
    }

    public function show(FlashSale $flashSale): JsonResponse
    {
        return response()->json([
            'flash_sale' => $flashSale->load(['product', 'variant']),
        ]);
    }

    public function update(Request $request, FlashSale $flashSale): JsonResponse
    {
        $data = $request->validate([
            'title_ar'      => 'sometimes|string|max:255',
            'title_en'      => 'sometimes|string|max:255',
            'product_id'    => 'sometimes|integer|exists:products,id',
            'variant_id'    => 'nullable|integer|exists:product_variants,id',
            'flash_price'   => 'sometimes|numeric|min:0',
            'max_quantity'  => 'sometimes|integer|min:1',
            'sold_quantity' => 'sometimes|integer|min:0',
            'start_date'    => 'sometimes|date',
            'end_date'      => 'sometimes|date|after:start_date',
            'is_active'     => 'boolean',
        ]);

        $flashSale->update($data);

        return response()->json([
            'message'    => 'Flash sale updated successfully.',
            'flash_sale' => $flashSale->fresh()->load(['product:id,name_ar,name_en,slug', 'variant:id,sku,regular_price']),
        ]);
    }

    public function destroy(FlashSale $flashSale): JsonResponse
    {
        $flashSale->delete();

        return response()->json(['message' => 'Flash sale deleted successfully.']);
    }
}
