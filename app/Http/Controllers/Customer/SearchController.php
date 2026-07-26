<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerProductListResource;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(protected SearchService $searchService) {}

    /**
     * Advanced product search with faceted filters.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q'          => 'nullable|string|max:200',
            'per_page'   => 'nullable|integer|min:1|max:100',
            'page'       => 'nullable|integer|min:1',
            'sort'       => 'nullable|in:relevance,price_asc,price_desc,newest,rating,best_selling',
            'category_id'=> 'nullable',
            'brand_id'   => 'nullable',
            'min_price'  => 'nullable|numeric|min:0',
            'max_price'  => 'nullable|numeric|min:0',
            'in_stock'   => 'nullable|boolean',
            'featured'   => 'nullable|boolean',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'attributes' => 'nullable|array',
        ]);

        $results = $this->searchService->search($request);

        $results->getCollection()->transform(fn($p) => new CustomerProductListResource($p));

        return response()->json($results);
    }

    /**
     * Auto-complete search suggestions.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|max:200',
        ]);

        $suggestions = $this->searchService->suggestions($request->get('q', ''));

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Get all faceted filter options.
     */
    public function filters(): JsonResponse
    {
        $filters = $this->searchService->getFacetedFilters();

        return response()->json($filters);
    }
}
