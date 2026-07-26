<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'tags' => Tag::withCount('products')->orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'slug'    => 'nullable|string|max:100|unique:tags,slug',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name_en']);

        $tag = Tag::create($data);

        return response()->json([
            'message' => 'Tag created successfully.',
            'tag'     => $tag,
        ], 201);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $data = $request->validate([
            'name_ar' => 'sometimes|string|max:100',
            'name_en' => 'sometimes|string|max:100',
            'slug'    => 'nullable|string|max:100|unique:tags,slug,' . $tag->id,
        ]);

        $tag->update($data);

        return response()->json([
            'message' => 'Tag updated successfully.',
            'tag'     => $tag->fresh(),
        ]);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(['message' => 'Tag deleted successfully.']);
    }
}
