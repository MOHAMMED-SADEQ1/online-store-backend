<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\ProductReview;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'variant_id'   => 'nullable|exists:product_variants,id',
            'rating'       => 'required|integer|min:1|max:5',
            'review_title' => 'nullable|string|max:255',
            'review_text'  => 'nullable|string|max:5000',
        ]);

        // Verify the user has purchased this product and it was delivered
        $purchased = OrderItem::whereHas('order', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)
              ->where('order_status', 'delivered');
        })->where('product_id', $data['product_id'])
          ->when($data['variant_id'] ?? null, fn($q, $v) => $q->where('variant_id', $v))
          ->exists();

        if (!$purchased) {
            return response()->json(['message' => __('review.only_purchased')], 422);
        }

        // Check duplicate
        $existing = ProductReview::where('user_id', $request->user()->id)
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => __('review.already_reviewed')], 422);
        }

        $data['user_id'] = $request->user()->id;
        $data['is_approved'] = false;

        $review = ProductReview::create($data);

        // Award review bonus points
        try {
            app(LoyaltyService::class)->awardReviewBonus($request->user());
        } catch (\Exception $e) {
            \Log::warning('Failed to award review bonus', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => __('review.submitted'),
            'review'  => [
                'id'           => $review->id,
                'rating'       => $review->rating,
                'review_title' => $review->review_title,
                'review_text'  => $review->review_text,
                'created_at'   => $review->created_at,
            ],
        ], 201);
    }

    /**
     * List products the user can review (purchased & delivered, not yet reviewed).
     */
    public function purchasable(Request $request): JsonResponse
    {
        $locale = app()->getLocale();

        $reviewedProductIds = ProductReview::where('user_id', $request->user()->id)
            ->pluck('product_id');

        $items = OrderItem::whereHas('order', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)
              ->where('order_status', 'delivered');
        })
            ->whereNotIn('product_id', $reviewedProductIds)
            ->with('product')
            ->get()
            ->unique('product_id')
            ->values()
            ->map(fn($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->{'name_' . $locale} ?? '',
                'product_slug' => $item->product?->slug ?? '',
                'product_image' => $item->product?->main_image ? url($item->product->main_image) : null,
                'variant_id' => $item->variant_id,
            ]);

        return response()->json(['products' => $items]);
    }
}
