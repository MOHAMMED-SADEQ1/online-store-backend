<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function index(Request $request, Product $product): JsonResponse
    {
        $query = $product->images()->orderBy('display_order');

        if ($request->has('variant_id')) {
            $query->where('variant_id', $request->variant_id);
        }

        $images = $query->get()->map(fn($img) => $this->formatImage($img));

        return response()->json(['images' => $images]);
    }

    public function show(Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            return response()->json(['message' => 'Image does not belong to this product.'], 404);
        }

        return response()->json(['image' => $this->formatImage($image)]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'image'         => 'required_without:image_url|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_url'     => 'required_without:image|string|max:255',
            'variant_id'    => 'nullable|exists:product_variants,id',
            'alt_text'      => 'nullable|string|max:255',
            'display_order' => 'integer|min:0',
            'is_main'       => 'boolean',
        ]);

        if (isset($data['variant_id'])) {
            $variant = ProductVariant::findOrFail($data['variant_id']);
            if ($variant->product_id !== $product->id) {
                return response()->json(['message' => 'Variant does not belong to this product.'], 422);
            }
        }

        $data['product_id'] = $product->id;
        $data['image_url']  = $this->handleUpload($request, $data);

        if ($data['is_main'] ?? false) {
            $product->images()->update(['is_main' => false]);
        }

        $image = ProductImage::create($data);

        return response()->json([
            'message' => 'Image added successfully.',
            'image'   => $this->formatImage($image),
        ], 201);
    }

    public function update(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            return response()->json(['message' => 'Image does not belong to this product.'], 404);
        }

        $data = $request->validate([
            'image'         => 'sometimes|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_url'     => 'sometimes|string|max:255',
            'variant_id'    => 'nullable|exists:product_variants,id',
            'alt_text'      => 'nullable|string|max:255',
            'display_order' => 'integer|min:0',
            'is_main'       => 'boolean',
        ]);

        if (isset($data['variant_id'])) {
            $variant = ProductVariant::findOrFail($data['variant_id']);
            if ($variant->product_id !== $product->id) {
                return response()->json(['message' => 'Variant does not belong to this product.'], 422);
            }
        }

        $hasNewFile = $request->hasFile('image');
        if ($hasNewFile) {
            $this->deleteImageFile($image->image_url);
            $data['image_url'] = $this->storeUploadedFile($request, $data['variant_id'] ?? $image->variant_id);
        }

        if (($data['is_main'] ?? false) && !$hasNewFile) {
            $product->images()->where('id', '!=', $image->id)->update(['is_main' => false]);
        }

        if (($data['is_main'] ?? false) && $hasNewFile) {
            $product->images()->update(['is_main' => false]);
        }

        $image->update($data);

        return response()->json([
            'message' => 'Image updated successfully.',
            'image'   => $this->formatImage($image->fresh()),
        ]);
    }

    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            return response()->json(['message' => 'Image does not belong to this product.'], 404);
        }

        $this->deleteImageFile($image->image_url);
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully.']);
    }

    public function variantImages(Product $product, ProductVariant $variant): JsonResponse
    {
        if ($variant->product_id !== $product->id) {
            return response()->json(['message' => 'Variant does not belong to this product.'], 404);
        }

        $images = $product->images()
            ->where('variant_id', $variant->id)
            ->orderBy('display_order')
            ->get()
            ->map(fn($img) => $this->formatImage($img));

        return response()->json(['images' => $images]);
    }

    // ── Helpers ──

    private function handleUpload(Request $request, array &$data): string
    {
        if ($request->hasFile('image')) {
            return $this->storeUploadedFile($request, $data['variant_id'] ?? null);
        }
        return $data['image_url'];
    }

    private function storeUploadedFile(Request $request, ?int $variantId): string
    {
        $folder = $variantId ? 'variants' : 'products';
        return $request->file('image')->store($folder, 'public');
    }

    private function deleteImageFile(string $path): void
    {
        if ($path && !str_starts_with($path, 'http') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function imageUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return url('storage/' . $path);
    }

    private function formatImage(ProductImage $image): array
    {
        return [
            'id'             => $image->id,
            'product_id'     => $image->product_id,
            'variant_id'     => $image->variant_id,
            'image_url'      => $this->imageUrl($image->image_url),
            'alt_text'       => $image->alt_text,
            'display_order'  => $image->display_order,
            'is_main'        => $image->is_main,
        ];
    }
}