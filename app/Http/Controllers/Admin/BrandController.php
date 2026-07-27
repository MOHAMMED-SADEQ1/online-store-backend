<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'brands' => Brand::withCount('products')->orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar'          => 'required|string|max:100',
            'name_en'          => 'required|string|max:100',
            'slug'             => 'nullable|string|max:100|unique:brands,slug',
            'logo'             => 'nullable|string|max:255',
            'description_ar'   => 'nullable|string',
            'description_en'   => 'nullable|string',
            'is_active'        => 'boolean',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name_en']);

        $brand = Brand::create($data);

        return response()->json([
            'message' => 'Brand created successfully.',
            'brand'   => $brand,
        ], 201);
    }

    public function show(Brand $brand): JsonResponse
    {
        return response()->json([
            'brand' => $brand->loadCount('products'),
        ]);
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $data = $request->validate([
            'name_ar'          => 'sometimes|string|max:100',
            'name_en'          => 'sometimes|string|max:100',
            'slug'             => 'nullable|string|max:100|unique:brands,slug,' . $brand->id,
            'logo'             => 'nullable|string|max:255',
            'description_ar'   => 'nullable|string',
            'description_en'   => 'nullable|string',
            'is_active'        => 'boolean',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $brand->update($data);

        return response()->json([
            'message' => 'Brand updated successfully.',
            'brand'   => $brand->fresh(),
        ]);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete brand with associated products.',
                'code'    => 409,
            ], 409);
        }

        $brand->delete();

        return response()->json(['message' => 'Brand deleted successfully.']);
    }
}
