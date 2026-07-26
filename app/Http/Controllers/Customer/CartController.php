<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerCartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cart = Cart::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['session_id' => null]
        );

        $cart->load(['items.product.images' => fn($q) => $q->whereNull('variant_id'), 'items.variant']);

        return response()->json([
            'cart' => new CustomerCartResource($cart),
        ]);
    }

    private function checkItemAvailability(Product $product, ?ProductVariant $variant, int $requestedQty, int $existingQty = 0): void
    {
        if (!$product->is_active) {
            abort(422, __('order.product_unavailable'));
        }

        $totalQty = $existingQty + $requestedQty;
        $stock = $variant ? $variant->stock_quantity : $product->quantity_in_stock;

        if ($totalQty > $stock) {
            abort(422, __('cart.quantity_exceeds_stock'));
        }

        if ($product->max_per_order && $totalQty > $product->max_per_order) {
            abort(422, __('cart.max_per_order', ['max' => $product->max_per_order]));
        }

        if ($variant && !$variant->is_active) {
            abort(422, __('order.variant_unavailable'));
        }
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $variantId = $data['variant_id'] ?? null;
        $product = Product::findOrFail($data['product_id']);
        $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;

        $this->checkItemAvailability($product, $variant, $data['quantity']);

        $cart = Cart::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['session_id' => null]
        );

        // Check if already in cart
        $existing = $cart->items()
            ->where('product_id', $data['product_id'])
            ->where('variant_id', $variantId)
            ->first();

        if ($existing) {
            $existingQty = $existing->quantity;
            $this->checkItemAvailability($product, $variant, $data['quantity'], $existingQty);
            $existing->increment('quantity', $data['quantity']);
        } else {
            $itemData = [
                'cart_id'    => $cart->id,
                'product_id' => $data['product_id'],
                'variant_id' => $variantId,
                'quantity'   => $data['quantity'],
            ];
            $cart->items()->create($itemData);
        }

        $cart->load(['items.product.images' => fn($q) => $q->whereNull('variant_id'), 'items.variant']);

        return response()->json([
            'message' => __('cart.added'),
            'cart'    => new CustomerCartResource($cart),
        ]);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        if ($cartItem->cart->user_id !== $request->user()->id) {
            return response()->json(['message' => __('cart.unauthorized')], 403);
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = $cartItem->product ?? Product::findOrFail($cartItem->product_id);
        $variant = $cartItem->variant_id ? ($cartItem->variant ?? ProductVariant::findOrFail($cartItem->variant_id)) : null;

        $this->checkItemAvailability($product, $variant, $data['quantity']);

        $cartItem->update($data);

        $cart = $cartItem->cart;
        $cart->load(['items.product.images' => fn($q) => $q->whereNull('variant_id'), 'items.variant']);

        return response()->json([
            'message' => __('cart.updated'),
            'cart'    => new CustomerCartResource($cart),
        ]);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        if ($cartItem->cart->user_id !== $request->user()->id) {
            return response()->json(['message' => __('cart.unauthorized')], 403);
        }

        $cart = $cartItem->cart;
        $cartItem->delete();

        $cart->load(['items.product.images' => fn($q) => $q->whereNull('variant_id'), 'items.variant']);

        return response()->json([
            'message' => __('cart.removed'),
            'cart'    => new CustomerCartResource($cart),
        ]);
    }

    public function applyCoupon(Request $request, CouponService $couponService): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->with('items')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => __('cart.empty')], 422);
        }

        // Prepare cart items for coupon validation
        $cartItems = $cart->items->map(fn($i) => [
            'product_id' => $i->product_id,
            'variant_id' => $i->variant_id,
            'quantity'   => $i->quantity,
        ])->toArray();

        $subtotal = $cart->items->sum(fn($i) => $i->quantity * ($i->variant?->sale_price ?? $i->product?->sale_price ?? $i->variant?->regular_price ?? $i->product?->regular_price ?? 0));

        $result = $couponService->validate(
            $data['code'],
            $subtotal,
            $request->user()->id,
            $cartItems,
        );

        if (!$result['valid']) {
            return response()->json([
                'valid'  => false,
                'message'=> $result['message'],
            ], 422);
        }

        $cart->forceFill([
            'coupon_code'     => $data['code'],
            'coupon_discount' => $result['discount_amount'],
        ])->save();

        $cart->load(['items.product.images' => fn($q) => $q->whereNull('variant_id'), 'items.variant']);

        return response()->json([
            'valid'            => true,
            'message'          => __('cart.coupon_applied'),
            'discount_amount'  => $result['discount_amount'],
            'is_free_shipping' => $result['is_free_shipping'] ?? false,
            'cart'             => new CustomerCartResource($cart),
        ]);
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if ($cart) {
            $cart->forceFill(['coupon_code' => null, 'coupon_discount' => 0])->save();
            $cart->load(['items.product.images' => fn($q) => $q->whereNull('variant_id'), 'items.variant']);
        }

        return response()->json([
            'message' => __('cart.coupon_removed'),
            'cart'    => $cart ? new CustomerCartResource($cart) : null,
        ]);
    }
}
