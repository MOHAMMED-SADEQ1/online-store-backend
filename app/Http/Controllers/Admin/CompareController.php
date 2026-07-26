<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompareItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = CompareItem::with('user:id,username,email', 'product')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->session_id, fn($q, $v) => $q->where('session_id', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'    => 'nullable|exists:users,id',
            'session_id' => 'nullable|string|max:255',
            'product_id' => 'required|exists:products,id',
        ]);

        $item = CompareItem::create($data);

        return response()->json([
            'message' => 'Item added to compare.',
            'item'    => $item->load('user', 'product'),
        ], 201);
    }

    public function show(CompareItem $compareItem): JsonResponse
    {
        $compareItem->load('user', 'product');

        return response()->json(['item' => $compareItem]);
    }

    public function destroy(CompareItem $compareItem): JsonResponse
    {
        $compareItem->delete();

        return response()->json(['message' => 'Compare item removed.']);
    }
}
