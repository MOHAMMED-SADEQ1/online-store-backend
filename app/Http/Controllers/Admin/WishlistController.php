<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Wishlist::with('user:id,username,email', 'product', 'variant')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->product_id, fn($q, $v) => $q->where('product_id', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $item = Wishlist::create($data);

        return response()->json([
            'message' => 'Item added to wishlist.',
            'wishlist' => $item->load('user', 'product', 'variant'),
        ], 201);
    }

    public function show(Wishlist $wishlist): JsonResponse
    {
        $wishlist->load('user', 'product', 'variant');

        return response()->json(['wishlist' => $wishlist]);
    }

    public function update(Request $request, Wishlist $wishlist): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $wishlist->update($data);

        return response()->json([
            'message' => 'Wishlist item updated.',
            'wishlist' => $wishlist->fresh()->load('user', 'product', 'variant'),
        ]);
    }

    public function destroy(Wishlist $wishlist): JsonResponse
    {
        $wishlist->delete();

        return response()->json(['message' => 'Wishlist item removed.']);
    }
}
