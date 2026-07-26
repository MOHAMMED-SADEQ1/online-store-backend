<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecentlyViewed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecentlyViewedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = RecentlyViewed::with('user:id,username,email', 'product', 'variant')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->product_id, fn($q, $v) => $q->where('product_id', $v))
            ->orderBy('viewed_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'session_id' => 'nullable|string|max:255',
            'viewed_at'  => 'nullable|date',
        ]);

        $item = RecentlyViewed::create($data);

        return response()->json([
            'message' => 'Recently viewed record created.',
            'item'    => $item->load('user', 'product', 'variant'),
        ], 201);
    }

    public function show(RecentlyViewed $recentlyViewed): JsonResponse
    {
        $recentlyViewed->load('user', 'product', 'variant');

        return response()->json(['item' => $recentlyViewed]);
    }

    public function destroy(RecentlyViewed $recentlyViewed): JsonResponse
    {
        $recentlyViewed->delete();

        return response()->json(['message' => 'Record removed.']);
    }
}
