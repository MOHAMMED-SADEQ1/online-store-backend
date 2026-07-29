<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::with(['categories', 'tags', 'attributes', 'variants', 'images'])
            ->when($request->search, fn($q) => $q->where('name_ar', 'like', "%{$request->search}%")
                ->orWhere('name_en', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%"))
            ->when($request->category_id, fn($q, $v) => $q->whereHas('categories', fn($q) => $q->where('id', $v)))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active))
            ->when($request->is_featured !== null, fn($q) => $q->where('is_featured', $request->is_featured))
            ->when($request->low_stock, fn($q) => $q->where('quantity_in_stock', '<=', DB::raw('low_stock_threshold')))
            ->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar'             => 'required|string|max:500',
            'name_en'             => 'required|string|max:500',
            'slug'                => 'nullable|string|max:255|unique:products,slug',
            'description_ar'      => 'nullable|string',
            'description_en'      => 'nullable|string',
            'sku'                 => 'required|string|max:100|unique:products,sku',
            'regular_price'       => 'required|numeric|min:0',
            'sale_price'          => 'nullable|numeric|min:0|lte:regular_price',
            'cost_price'          => 'nullable|numeric|min:0',
            'tax_rate_id'         => 'nullable|exists:tax_rates,id',
            'quantity_in_stock'   => 'integer|min:0',
            'low_stock_threshold' => 'integer|min:0',
            'weight'              => 'nullable|numeric|min:0',
            'dimensions'          => 'nullable|string|max:100',
            'main_image'          => 'nullable|string|max:255',
            'image'               => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_active'           => 'boolean',
            'is_featured'         => 'boolean',
            'is_returnable'       => 'boolean',
            'is_exchangeable'     => 'boolean',
            'return_period_days'  => 'integer|min:0',
            'is_cancellable'      => 'boolean',
            'categories'          => 'array',
            'categories.*'        => 'exists:categories,id',
            'tags'                => 'array',
            'tags.*'              => 'exists:tags,id',
            'attributes'          => 'array',
            'attributes.*'        => 'exists:attributes,id',
            'max_per_order'       => 'nullable|integer|min:1',
            'price_includes_tax'  => 'boolean',
            'meta_title'          => 'nullable|string|max:255',
            'meta_description'    => 'nullable|string|max:500',
        ]);

        // Handle image file upload → set main_image path
        if ($request->hasFile('image')) {
            $data['main_image'] = $request->file('image')->store('products', 'public');
        }

        $data['slug'] = $data['slug'] ?? Str::slug($data['name_en']);

        $product = DB::transaction(function () use ($data) {
            $product = Product::create($data);

            if (isset($data['categories'])) {
                $product->categories()->sync($data['categories']);
            }
            if (isset($data['tags'])) {
                $product->tags()->sync($data['tags']);
            }
            if (isset($data['attributes'])) {
                $product->attributes()->sync($data['attributes']);
            }

            return $product;
        });

        $product->load(['categories', 'tags', 'attributes']);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => new AdminProductResource($product),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['categories', 'tags', 'attributes', 'variants.attributeValues', 'variants.images', 'images' => fn($q) => $q->whereNull('variant_id')]);

        return response()->json([
            'product' => new AdminProductResource($product),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'name_ar'             => 'sometimes|string|max:500',
            'name_en'             => 'sometimes|string|max:500',
            'slug'                => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'description_ar'      => 'nullable|string',
            'description_en'      => 'nullable|string',
            'sku'                 => 'sometimes|string|max:100|unique:products,sku,' . $product->id,
            'regular_price'       => 'sometimes|numeric|min:0',
            'sale_price'          => 'nullable|numeric|min:0|lte:regular_price',
            'cost_price'          => 'nullable|numeric|min:0',
            'tax_rate_id'         => 'nullable|exists:tax_rates,id',
            'quantity_in_stock'   => 'integer|min:0',
            'low_stock_threshold' => 'integer|min:0',
            'weight'              => 'nullable|numeric|min:0',
            'dimensions'          => 'nullable|string|max:100',
            'main_image'          => 'nullable|string|max:255',
            'image'               => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_active'           => 'boolean',
            'is_featured'         => 'boolean',
            'is_returnable'       => 'boolean',
            'is_exchangeable'     => 'boolean',
            'return_period_days'  => 'integer|min:0',
            'is_cancellable'      => 'boolean',
            'categories'          => 'array',
            'categories.*'        => 'exists:categories,id',
            'tags'                => 'array',
            'tags.*'              => 'exists:tags,id',
            'attributes'          => 'array',
            'attributes.*'        => 'exists:attributes,id',
            'max_per_order'       => 'nullable|integer|min:1',
            'price_includes_tax'  => 'boolean',
            'meta_title'          => 'nullable|string|max:255',
            'meta_description'    => 'nullable|string|max:500',
        ]);

        // Handle image file upload → set main_image path ( delete old if exists )
        if ($request->hasFile('image')) {
            // Delete old main_image file if it exists and is not an external URL
            if ($product->main_image && !str_starts_with($product->main_image, 'http')) {
                Storage::disk('public')->delete($product->main_image);
            }
            $data['main_image'] = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($data, $product) {
            $product->update($data);

            if (isset($data['categories'])) {
                $product->categories()->sync($data['categories']);
            }
            if (isset($data['tags'])) {
                $product->tags()->sync($data['tags']);
            }
            if (isset($data['attributes'])) {
                $product->attributes()->sync($data['attributes']);
            }
        });

        $product->load(['categories', 'tags', 'attributes']);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => new AdminProductResource($product),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
