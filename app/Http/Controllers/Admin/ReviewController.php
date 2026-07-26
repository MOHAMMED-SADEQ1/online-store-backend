<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reviews = ProductReview::with(['user:id,username,email,first_name,last_name', 'product:id,name_ar,name_en,sku'])
            ->when($request->is_approved !== null, fn($q) => $q->where('is_approved', $request->is_approved))
            ->when($request->rating, fn($q, $v) => $q->where('rating', $v))
            ->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($reviews);
    }

    public function approve(ProductReview $review): JsonResponse
    {
        $review->update(['is_approved' => true]);

        return response()->json([
            'message' => 'Review approved successfully.',
            'review'  => $review->fresh(),
        ]);
    }

    public function update(Request $request, ProductReview $review): JsonResponse
    {
        $data = $request->validate([
            'rating'       => 'sometimes|integer|min:1|max:5',
            'review_title' => 'nullable|string|max:255',
            'review_text'  => 'nullable|string',
            'is_approved'  => 'boolean',
        ]);

        $review->update($data);

        return response()->json([
            'message' => 'Review updated successfully.',
            'review'  => $review->fresh()->load('user:id,username,email,first_name,last_name', 'product:id,name_ar,name_en,sku'),
        ]);
    }

    public function destroy(ProductReview $review): JsonResponse
    {
        $review->delete();

        return response()->json(['message' => 'Review deleted successfully.']);
    }
}
