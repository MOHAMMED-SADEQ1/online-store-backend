<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilterController extends Controller
{
    public function filters(): JsonResponse
    {
        $locale = app()->getLocale();

        $priceRange = Product::where('is_active', true)
            ->selectRaw('MIN(COALESCE(sale_price, regular_price)) as min_price')
            ->selectRaw('MAX(COALESCE(sale_price, regular_price)) as max_price')
            ->first();

        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn($q) => $q->where('is_active', true)])
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'name'           => $c->{'name_' . $locale},
                'products_count' => $c->products_count,
            ]);

        $attributes = Attribute::where('is_global', true)
            ->with(['values' => function($q) {
                $q->select('attribute_values.*', DB::raw('COUNT(DISTINCT vav.variant_id) as products_count'))
                  ->leftJoin('variant_attribute_values as vav', 'attribute_values.id', '=', 'vav.value_id')
                  ->groupBy('attribute_values.id');
            }])
            ->get()
            ->map(fn($a) => [
                'id'      => $a->id,
                'name'    => $a->{'name_' . $locale},
                'values'  => $a->values->map(fn($v) => [
                    'id'       => $v->id,
                    'value'    => $v->{'value_' . $locale},
                ]),
            ]);

        return response()->json([
            'price_range' => [
                'min' => (float) ($priceRange->min_price ?? 0),
                'max' => (float) ($priceRange->max_price ?? 0),
            ],
            'categories' => $categories,
            'attributes' => $attributes,
        ]);
    }

    public function searchSuggestions(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        $term = $request->get('q', '');

        if (strlen($term) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $products = Product::where('is_active', true)
            ->where(function($q) use ($term) {
                $q->where('name_ar', 'like', "%{$term}%")
                  ->orWhere('name_en', 'like', "%{$term}%");
            })
            ->with(['images' => fn($q) => $q->whereNull('variant_id')->orderBy('display_order')])
            ->limit(8)
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->{'name_' . $locale},
                'image' => $p->images->first()
                    ? url('storage/' . $p->images->first()->image_url)
                    : null,
            ]);

        return response()->json(['suggestions' => $products]);
    }
}
