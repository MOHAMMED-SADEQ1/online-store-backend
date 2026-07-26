<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with('product', 'variant')
            ->latest()
            ->get()
            ->map(fn($w) => [
                'id'         => $w->id,
                'product_id' => $w->product_id,
                'variant_id' => $w->variant_id,
                'product'    => $w->product ? [
                    'id'      => $w->product->id,
                    'name' => $w->product->{'name_' . $locale},
                    'slug'    => $w->product->slug,
                    'regular_price' => (float) $w->product->regular_price,
                    'sale_price'    => (float) $w->product->sale_price,
                    'main_image'    => $w->product->main_image ? url($w->product->main_image) : null,
                ] : null,
            ]);

        return response()->json(['wishlist' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $existing = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $data['product_id'])
            ->where('variant_id', $data['variant_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => __('wishlist.already_exists')], 422);
        }

        $wishlist = Wishlist::create([
            'user_id'    => $request->user()->id,
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'] ?? null,
        ]);

        return response()->json([
            'message' => __('wishlist.added'),
            'id'      => $wishlist->id,
        ], 201);
    }

    public function destroy(Request $request, Wishlist $wishlist): JsonResponse
    {
        if ($wishlist->user_id !== $request->user()->id) {
            return response()->json(['message' => __('wishlist.unauthorized')], 403);
        }

        $wishlist->delete();

        return response()->json(['message' => __('wishlist.removed')]);
    }
}
