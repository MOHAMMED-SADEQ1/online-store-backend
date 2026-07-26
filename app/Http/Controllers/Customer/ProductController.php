<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerProductListResource;
use App\Http\Resources\Customer\CustomerProductResource;
use App\Models\Product;
use App\Models\RecentlyViewed;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->with([
                'categories',
                'images' => fn($q) => $q->whereNull('variant_id'),
                'variants' => fn($q) => $q->where('is_active', true)->with(['images' => fn($q) => $q->orderBy('display_order')]),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($request->search, fn($q, $v) => $q->where(function($q) use ($v) {
                $q->where('name_ar', 'like', "%{$v}%")
                  ->orWhere('name_en', 'like', "%{$v}%")
                  ->orWhere('description_ar', 'like', "%{$v}%")
                  ->orWhere('description_en', 'like', "%{$v}%");
            }))
            ->when($request->category_id, fn($q, $v) => $q->whereHas('categories', fn($q) => $q->where('id', $v)))
            ->when($request->min_price, fn($q, $v) => $q->where(function($q) use ($v) {
                $q->where('sale_price', '>=', $v)->orWhere('regular_price', '>=', $v);
            }))
            ->when($request->max_price, fn($q, $v) => $q->where(function($q) use ($v) {
                $q->where('sale_price', '<=', $v)->orWhere('regular_price', '<=', $v);
            }))
            ->when($request->sort, function($q, $v) {
                match ($v) {
                    'price_asc'  => $q->orderBy(DB::raw('COALESCE(sale_price, regular_price)'), 'asc'),
                    'price_desc' => $q->orderBy(DB::raw('COALESCE(sale_price, regular_price)'), 'desc'),
                    'newest'     => $q->latest(),
                    'oldest'     => $q->orderBy('created_at', 'asc'),
                    'name_asc'   => $q->orderBy('name_ar', 'asc'),
                    'name_desc'  => $q->orderBy('name_ar', 'desc'),
                    'rating'     => $q->orderByDesc('reviews_avg_rating'),
                    default      => $q->latest(),
                };
            })
            ->paginate($request->per_page ?? 20);

        $products->getCollection()->transform(fn($p) => new CustomerProductListResource($p));

        return response()->json($products);
    }

    public function show(string $slug, Product $product): JsonResponse
    {
        if (!$product->is_active || $product->slug !== $slug) {
            return response()->json(['message' => __('product.not_found')], 404);
        }

        $product->load([
            'categories',
            'tags',
            'attributes',
            'variants' => fn($q) => $q->where('is_active', true)->orderByRaw('COALESCE(sale_price, regular_price) ASC'),
            'variants.attributeValues.attribute',
            'variants.images' => fn($q) => $q->orderBy('display_order'),
            'images' => fn($q) => $q->whereNull('variant_id')->orderBy('display_order'),
            'reviews' => fn($q) => $q->where('is_approved', true)->with('user'),
        ]);

        return response()->json([
            'product' => new CustomerProductResource($product),
        ]);
    }

    public function related(string $slug, Product $product): JsonResponse
    {
        if (!$product->is_active || $product->slug !== $slug) {
            return response()->json(['message' => __('product.not_found')], 404);
        }

        $categoryIds = $product->categories()->pluck('categories.id');

        $related = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
            ->with([
                'categories',
                'images' => fn($q) => $q->whereNull('variant_id'),
                'variants' => fn($q) => $q->where('is_active', true)->with(['images' => fn($q) => $q->orderBy('display_order')]),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->limit(8)
            ->get();

        return response()->json([
            'products' => CustomerProductListResource::collection($related),
        ]);
    }

    public function frequentlyBought(string $slug, Product $product): JsonResponse
    {
        if (!$product->is_active || $product->slug !== $slug) {
            return response()->json(['message' => __('product.not_found')], 404);
        }

        $boughtTogether = DB::table('order_items as oi1')
            ->join('order_items as oi2', 'oi1.order_id', '=', 'oi2.order_id')
            ->join('products', 'oi2.product_id', '=', 'products.id')
            ->where('oi1.product_id', $product->id)
            ->where('oi2.product_id', '!=', $product->id)
            ->where('products.is_active', true)
            ->select('oi2.product_id', DB::raw('COUNT(*) as times_bought_together'))
            ->groupBy('oi2.product_id')
            ->orderByDesc('times_bought_together')
            ->limit(4)
            ->pluck('product_id');

        $products = Product::whereIn('id', $boughtTogether)
            ->with([
                'categories',
                'images' => fn($q) => $q->whereNull('variant_id'),
                'variants' => fn($q) => $q->where('is_active', true)->with(['images' => fn($q) => $q->orderBy('display_order')]),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get();

        return response()->json([
            'products' => CustomerProductListResource::collection($products),
        ]);
    }

    public function recentlyViewed(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        RecentlyViewed::updateOrCreate(
            [
                'user_id'    => $request->user()->id,
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'] ?? null,
            ],
            ['viewed_at'  => Carbon::now()]
        );

        return response()->json(['message' => __('product.view_recorded')]);
    }
}
