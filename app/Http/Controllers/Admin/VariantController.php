<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    public function __construct(protected ProductVariantService $variantService) {}

    public function index(Product $product): JsonResponse
    {
        $variants = $this->variantService->getVariants($product);

        return response()->json([
            'variants' => AdminVariantResource::collection($variants),
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'sku'              => 'nullable|string|max:100|unique:product_variants,sku',
            'regular_price'    => 'required|numeric|min:0',
            'sale_price'       => 'nullable|numeric|min:0|lte:regular_price',
            'cost_price'       => 'nullable|numeric|min:0',
            'stock_quantity'   => 'integer|min:0',
            'barcode'          => 'nullable|string|max:100',
            'is_active'        => 'boolean',
            'attribute_values' => 'array|exists:attribute_values,id',
        ]);

        $variant = $this->variantService->createVariant($product, $data);

        return response()->json([
            'message' => 'Variant created successfully.',
            'variant' => new AdminVariantResource($variant),
        ], 201);
    }

    public function update(Request $request, Product $product, ProductVariant $variant): JsonResponse
    {
        $data = $request->validate([
            'sku'              => 'nullable|string|max:100|unique:product_variants,sku,' . $variant->id,
            'regular_price'    => 'sometimes|numeric|min:0',
            'sale_price'       => 'nullable|numeric|min:0|lte:regular_price',
            'cost_price'       => 'nullable|numeric|min:0',
            'stock_quantity'   => 'integer|min:0',
            'barcode'          => 'nullable|string|max:100',
            'is_active'        => 'boolean',
            'attribute_values' => 'array|exists:attribute_values,id',
        ]);

        $variant = $this->variantService->updateVariant($product, $variant, $data);

        return response()->json([
            'message' => 'Variant updated successfully.',
            'variant' => new AdminVariantResource($variant),
        ]);
    }

    public function destroy(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->variantService->deleteVariant($product, $variant);

        return response()->json(['message' => 'Variant deleted successfully.']);
    }
}
