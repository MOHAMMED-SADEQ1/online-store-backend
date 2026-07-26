<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $carts = Cart::with('user:id,username,email', 'items.product', 'items.variant')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->session_id, fn($q, $v) => $q->where('session_id', $v))
            ->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($carts);
    }

    public function show(Cart $cart): JsonResponse
    {
        $cart->load('user', 'items.product', 'items.variant');

        return response()->json(['cart' => $cart]);
    }

    public function destroy(Cart $cart): JsonResponse
    {
        $cart->items()->delete();
        $cart->delete();

        return response()->json(['message' => 'Cart deleted.']);
    }

    public function items(Cart $cart): JsonResponse
    {
        $items = $cart->items()->with('product', 'variant')->get();

        return response()->json(['items' => $items]);
    }

    public function updateItem(Request $request, Cart $cart, CartItem $item): JsonResponse
    {
        if ($item->cart_id !== $cart->id) {
            return response()->json(['message' => 'Item not in cart.'], 404);
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item->update($data);

        return response()->json([
            'message' => 'Cart item quantity updated.',
            'item'    => $item->fresh()->load('product', 'variant'),
        ]);
    }

    public function removeItem(Cart $cart, CartItem $item): JsonResponse
    {
        if ($item->cart_id !== $cart->id) {
            return response()->json(['message' => 'Item not in cart.'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Item removed from cart.']);
    }
}
