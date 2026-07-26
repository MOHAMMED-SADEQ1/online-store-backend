<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function compare(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_ids' => 'required|array|min:2|max:4',
            'product_ids.*' => 'exists:products,id',
        ]);

        $products = Product::whereIn('id', $data['product_ids'])
            ->where('is_active', true)
            ->with([
                'categories',
                'attributes',
                'variants' => fn($q) => $q->where('is_active', true),
                'variants.attributeValues.attribute',
                'variants.images' => fn($q) => $q->orderBy('display_order'),
                'images' => fn($q) => $q->whereNull('variant_id')->orderBy('display_order'),
            ])
            ->get();

        return response()->json([
            'products' => CustomerProductResource::collection($products),
        ]);
    }
}
