<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerCategoryResource;
use App\Http\Resources\Customer\CustomerProductListResource;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $locale = app()->getLocale();

        $featured = Product::with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->take(8)
            ->get();

        $newArrivals = Product::with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
            ->where('is_active', true)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->take(8)
            ->get();

        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->withCount('products')
            ->orderBy('display_order')
            ->take(6)
            ->get();

        $bestSellers = Product::with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
            ->where('is_active', true)
            ->whereIn('id', OrderItem::select('product_id')->groupBy('product_id')->pluck('product_id'))
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->take(4)
            ->get();

        $sliderProducts = Product::where('is_active', true)
            ->where('is_featured', true)
            ->take(5)
            ->get();

        // Personalized recommendations for authenticated users
        $personalized = null;
        if ($request->user()) {
            $personalizedIds = $this->recommendationService->personalizedRecommendations(
                $request->user()->id, 6
            );
            $personalized = CustomerProductListResource::collection(
                Product::whereIn('id', collect($personalizedIds)->pluck('id'))
                    ->with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
                    ->get()
            );
        }

        return response()->json([
            'slider'       => $sliderProducts->map(fn($p) => [
                'id'     => $p->id,
                'name'   => $p->{'name_' . $locale},
                'slug'   => $p->slug,
                'image'  => $p->main_image ? url($p->main_image) : null,
                'regular_price' => (float) $p->regular_price,
                'sale_price'    => (float) $p->sale_price,
            ]),
            'featured_products'  => CustomerProductListResource::collection($featured),
            'new_arrivals'       => CustomerProductListResource::collection($newArrivals),
            'best_sellers'       => CustomerProductListResource::collection($bestSellers),
            'personalized'       => $personalized,
            'categories'         => CustomerCategoryResource::collection($categories),
            'locale'             => $locale,
        ]);
    }

    /**
     * Get top selling products.
     */
    public function topSelling(Request $request): JsonResponse
    {
        $products = $this->recommendationService->topSelling(10);

        return response()->json([
            'products' => CustomerProductListResource::collection($products),
        ]);
    }
}
