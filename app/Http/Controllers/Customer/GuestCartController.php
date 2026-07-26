<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerCartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestCartController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'guest_token'     => 'required|string|max:255',
        ]);

        $cart = Cart::firstOrCreate(
            ['session_id' => $data['guest_token']],
            ['user_id' => null]
        );

        foreach ($data['items'] as $itemData) {
            $variantId = $itemData['variant_id'] ?? null;

            $existing = $cart->items()
                ->where('product_id', $itemData['product_id'])
                ->where('variant_id', $variantId)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $itemData['quantity']);
            } else {
                $cart->items()->create([
                    'cart_id'    => $cart->id,
                    'product_id' => $itemData['product_id'],
                    'variant_id' => $variantId,
                    'quantity'   => $itemData['quantity'],
                ]);
            }
        }

        $cart->load(['items.product.images' => fn($q) => $q->whereNull('variant_id'), 'items.variant']);

        return response()->json([
            'cart' => new CustomerCartResource($cart),
        ]);
    }
}
