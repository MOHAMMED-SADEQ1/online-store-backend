<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::with('children')
            ->when($request->has('flat'), fn($q) => $q, fn($q) => $q->whereNull('parent_id'))
            ->withCount('products')
            ->orderBy('display_order')
            ->get();

        return response()->json(['categories' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar'        => 'required|string|max:100',
            'name_en'        => 'required|string|max:100',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'parent_id'      => 'nullable|exists:categories,id',
            'slug'           => 'nullable|string|max:100|unique:categories,slug',
            'image'          => 'nullable',
            'meta_title'     => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'display_order'  => 'integer|min:0',
            'is_active'      => 'boolean',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name_en']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        } elseif (!empty($data['image']) && !str_starts_with($data['image'], 'http')) {
            $data['image'] = null;
        }

        $category = Category::create($data);

        return response()->json([
            'message'  => 'Category created successfully.',
            'category' => $category->load('children'),
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        $category->load('children', 'parent');

        return response()->json(['category' => $category]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name_ar'        => 'sometimes|string|max:100',
            'name_en'        => 'sometimes|string|max:100',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'parent_id'      => 'nullable|exists:categories,id',
            'slug'           => 'nullable|string|max:100|unique:categories,slug,' . $category->id,
            'image'          => 'nullable',
            'meta_title'     => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'display_order'  => 'integer|min:0',
            'is_active'      => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImageFile($category->getRawOriginal('image'));
            $data['image'] = $request->file('image')->store('categories', 'public');
        } elseif (!array_key_exists('image', $data) || $data['image'] === null) {
            if (array_key_exists('image', $data) && $data['image'] === null) {
                $this->deleteImageFile($category->getRawOriginal('image'));
            }
            unset($data['image']);
        } elseif (!str_starts_with($data['image'], 'http')) {
            unset($data['image']);
        }

        $category->update($data);

        return response()->json([
            'message'  => 'Category updated successfully.',
            'category' => $category->fresh()->load('children'),
        ]);
    }

    private function deleteImageFile(?string $path): void
    {
        if ($path && !str_starts_with($path, 'http') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories.',
                'code'    => 409,
            ], 409);
        }

        $this->deleteImageFile($category->getRawOriginal('image'));
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}