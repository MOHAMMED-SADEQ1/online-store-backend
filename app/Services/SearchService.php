<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    /**
     * Advanced search using Meilisearch with faceted filters.
     */
    public function search(Request $request): LengthAwarePaginator
    {
        $query = $request->get('q', '');
        $perPage = (int) $request->get('per_page', 20);
        $page = (int) $request->get('page', 1);

        // Get filter parameters
        $categoryIds = $request->get('category_id');
        $brandIds = $request->get('brand_id');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $inStock = $request->get('in_stock');
        $featured = $request->get('featured');
        $rating = $request->get('min_rating');
        $attributeFilters = $request->get('attributes', []); // e.g. [1 => ['أحمر', 'أزرق']]

        $sort = $request->get('sort', 'relevance');

        $scoutQuery = Product::search($query);

        // ── Filters ──────────────────────────────────────────────

        $filters = [];

        if ($categoryIds) {
            $ids = is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds);
            $filters[] = 'category_id IN [' . implode(', ', $ids) . ']';
        }

        if ($brandIds) {
            $ids = is_array($brandIds) ? $brandIds : explode(',', $brandIds);
            $filters[] = 'brand_id IN [' . implode(', ', $ids) . ']';
        }

        if ($minPrice !== null) {
            $filters[] = "price >= {$minPrice}";
        }

        if ($maxPrice !== null) {
            $filters[] = "price <= {$maxPrice}";
        }

        if ($inStock) {
            $filters[] = 'stock_status != out_of_stock';
        }

        if ($featured) {
            $filters[] = 'is_featured = true';
        }

        if ($rating) {
            $filters[] = "rating >= {$rating}";
        }

        // Attribute filters (Meilisearch doesn't natively support EAV, so we filter after)
        // We handle attribute filtering as a post-search filter

        if (!empty($filters)) {
            $scoutQuery->where(implode(' AND ', $filters));
        }

        // ── Sorting ──────────────────────────────────────────────

        $scoutQuery->orderBy(match ($sort) {
            'price_asc'    => ['price' => 'asc'],
            'price_desc'   => ['price' => 'desc'],
            'newest'       => ['created_at' => 'desc'],
            'rating'       => ['rating' => 'desc'],
            'best_selling' => ['sold_count' => 'desc'],
            default        => [], // relevance
        });

        // ── Paginate ─────────────────────────────────────────────

        $results = $scoutQuery->paginate($perPage, 'page', $page);

        // Post-filter for attribute values if needed
        if (!empty($attributeFilters) && $results->isNotEmpty()) {
            $filtered = $results->filter(function ($product) use ($attributeFilters) {
                return $this->matchesAttributeFilters($product, $attributeFilters);
            });

            $results = new LengthAwarePaginator(
                $filtered->values(),
                $filtered->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // Load relationships for the results
        $productIds = collect($results->items())->pluck('id');
        $products = Product::whereIn('id', $productIds)
            ->with([
                'categories',
                'images' => fn($q) => $q->whereNull('variant_id'),
                'variants' => fn($q) => $q->where('is_active', true)
                    ->with(['images' => fn($q) => $q->orderBy('display_order')]),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get()
            ->keyBy('id');

        // Preserve scout's ranking order
        $sorted = collect($results->items())->map(fn($s) => $products->get($s->id))
            ->filter()
            ->values();

        return new LengthAwarePaginator(
            $sorted,
            $results->total(),
            $results->perPage(),
            $results->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Get search suggestions (auto-complete).
     */
    public function suggestions(string $query, int $limit = 8): array
    {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        $locale = app()->getLocale();

        $productIds = Product::search($query)
            ->take($limit)
            ->get()
            ->pluck('id');

        $products = Product::whereIn('id', $productIds)
            ->with(['images' => fn($q) => $q->whereNull('variant_id')->orderBy('display_order')])
            ->get();

        return $products->map(fn($p) => [
            'id'    => $p->id,
            'name'  => $p->{'name_' . $locale},
            'slug'  => $p->slug,
            'price' => (float) ($p->sale_price ?? $p->regular_price),
            'image' => $p->images->first()
                ? url('storage/' . $p->images->first()->image_url)
                : null,
        ])->toArray();
    }

    /**
     * Get faceted filter options for the search page.
     */
    public function getFacetedFilters(): array
    {
        $locale = app()->getLocale();

        return Cache::remember('search_filters_' . $locale, 3600, function () use ($locale) {
            $categories = \App\Models\Category::where('is_active', true)
                ->withCount(['products' => fn($q) => $q->where('is_active', true)])
                ->get()
                ->map(fn($c) => [
                    'id'             => $c->id,
                    'name'           => $c->{'name_' . $locale},
                    'slug'           => $c->slug,
                    'products_count' => $c->products_count,
                ]);

            $brands = \App\Models\Brand::whereHas('products', fn($q) => $q->where('is_active', true))
                ->withCount(['products' => fn($q) => $q->where('is_active', true)])
                ->get()
                ->map(fn($b) => [
                    'id'             => $b->id,
                    'name'           => $b->{'name_' . $locale},
                    'products_count' => $b->products_count,
                ]);

            $priceRange = Product::where('is_active', true)
                ->selectRaw('MIN(COALESCE(sale_price, regular_price)) as min_price')
                ->selectRaw('MAX(COALESCE(sale_price, regular_price)) as max_price')
                ->first();

            $attributes = \App\Models\Attribute::where('is_global', true)
                ->with(['values' => function($q) {
                    $q->select('attribute_values.*')
                        ->selectRaw('COUNT(DISTINCT variant_attribute_values.variant_id) as products_count')
                        ->leftJoin('variant_attribute_values', 'attribute_values.id', '=', 'variant_attribute_values.value_id')
                        ->groupBy('attribute_values.id');
                }])
                ->get()
                ->map(fn($a) => [
                    'id'     => $a->id,
                    'name'   => $a->{'name_' . $locale},
                    'type'   => $a->attribute_type,
                    'values' => $a->values->map(fn($v) => [
                        'id'             => $v->id,
                        'value'          => $v->{'value_' . $locale},
                        'extra_data'     => $v->extra_data,
                        'products_count' => (int) $v->products_count,
                    ]),
                ]);

            return [
                'categories'  => $categories,
                'brands'      => $brands,
                'price_range' => [
                    'min' => (float) ($priceRange->min_price ?? 0),
                    'max' => (float) ($priceRange->max_price ?? 0),
                ],
                'attributes'  => $attributes,
            ];
        });
    }

    /**
     * Check if a product matches the given attribute filters.
     */
    private function matchesAttributeFilters(Product $product, array $attributeFilters): bool
    {
        foreach ($attributeFilters as $attributeId => $values) {
            $productValues = $product->variants()
                ->whereHas('attributeValues', fn($q) => $q->where('attribute_id', $attributeId))
                ->with(['attributeValues' => fn($q) => $q->where('attribute_id', $attributeId)])
                ->get()
                ->pluck('attributeValues.*.value_' . app()->getLocale())
                ->flatten()
                ->unique();

            $hasMatch = collect($values)->intersect($productValues)->isNotEmpty();
            if (!$hasMatch) {
                return false;
            }
        }

        return true;
    }
}
