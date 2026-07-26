<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerCategoryResource;
use App\Http\Resources\Customer\CustomerProductListResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn($q) => $q->where('is_active', true)])
            ->withCount('products')
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'categories' => CustomerCategoryResource::collection($categories),
        ]);
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        if (!$category->is_active) {
            return response()->json(['message' => __('product.not_found')], 404);
        }

        $category->load(['children' => fn($q) => $q->where('is_active', true)]);

        $products = $category->products()
            ->where('is_active', true)
            ->with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->paginate($request->per_page ?? 20);

        $products->getCollection()->transform(fn($p) => new CustomerProductListResource($p));

        return response()->json([
            'category' => new CustomerCategoryResource($category),
            'products' => $products,
        ]);
    }
}
