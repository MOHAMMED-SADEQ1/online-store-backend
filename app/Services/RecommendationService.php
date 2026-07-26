<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Get products frequently bought together with the given product.
     * Uses order history to find co-purchased products.
     */
    public function frequentlyBoughtTogether(int $productId, int $limit = 4)
    {
        $boughtTogether = DB::table('order_items as oi1')
            ->join('order_items as oi2', 'oi1.order_id', '=', 'oi2.order_id')
            ->join('orders', 'oi1.order_id', '=', 'orders.id')
            ->join('products', 'oi2.product_id', '=', 'products.id')
            ->where('oi1.product_id', $productId)
            ->where('oi2.product_id', '!=', $productId)
            ->where('products.is_active', true)
            ->whereIn('orders.order_status', ['delivered', 'shipped', 'confirmed'])
            ->select('oi2.product_id', DB::raw('COUNT(DISTINCT oi1.order_id) as times_bought'))
            ->groupBy('oi2.product_id')
            ->orderByDesc('times_bought')
            ->limit($limit)
            ->pluck('product_id');

        if ($boughtTogether->isEmpty()) {
            $product = Product::with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
                ->find($productId);
            if (!$product) return collect();

            $categoryIds = $product->categories()->pluck('categories.id');

            return Product::with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
                ->where('is_active', true)
                ->where('id', '!=', $productId)
                ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
                ->limit($limit)
                ->get();
        }

        return Product::with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
            ->whereIn('id', $boughtTogether)->get();
    }

    /**
     * Get personalized product recommendations for a user based on their order history.
     */
    public function personalizedRecommendations(int $userId, int $limit = 10)
    {
        $categoryIds = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.id', '=', 'product_categories.product_id')
            ->where('order_items.order_id', function ($q) use ($userId) {
                $q->select('id')->from('orders')
                    ->where('user_id', $userId)
                    ->whereIn('order_status', ['delivered', 'shipped', 'confirmed']);
            })
            ->select('product_categories.category_id')
            ->distinct()
            ->pluck('category_id');

        if ($categoryIds->isEmpty()) {
            return Product::with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->limit($limit)
                ->get();
        }

        $purchasedIds = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->pluck('order_items.product_id');

        return Product::with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
            ->where('is_active', true)
            ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
            ->whereNotIn('id', $purchasedIds)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Get top selling products.
     */
    public function topSelling(int $limit = 10)
    {
        $topIds = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereHas('order', fn($q) => $q->where('payment_status', 'paid'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->pluck('product_id');

        if ($topIds->isEmpty()) {
            return Product::with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->where('is_active', true)
                ->limit($limit)
                ->get();
        }

        return Product::with(['categories', 'images' => fn($q) => $q->whereNull('variant_id')])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->whereIn('id', $topIds)
            ->get();
    }
}
