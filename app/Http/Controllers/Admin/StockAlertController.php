<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alerts = StockAlert::with('user:id,username,email', 'product', 'variant')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->is_notified !== null, fn($q) => $q->where('is_notified', $request->is_notified))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($alerts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'email'      => 'required|email|max:100',
            'phone'      => 'nullable|string|max:20',
        ]);

        $alert = StockAlert::create($data);

        return response()->json([
            'message' => 'Stock alert created.',
            'alert'   => $alert->load('user', 'product', 'variant'),
        ], 201);
    }

    public function show(StockAlert $stockAlert): JsonResponse
    {
        $stockAlert->load('user', 'product', 'variant');

        return response()->json(['alert' => $stockAlert]);
    }

    public function update(Request $request, StockAlert $stockAlert): JsonResponse
    {
        $data = $request->validate([
            'email'       => 'sometimes|email|max:100',
            'phone'       => 'nullable|string|max:20',
            'is_notified' => 'boolean',
            'notified_at' => 'nullable|date',
        ]);

        $stockAlert->update($data);

        return response()->json([
            'message' => 'Stock alert updated.',
            'alert'   => $stockAlert->fresh()->load('user', 'product', 'variant'),
        ]);
    }

    public function destroy(StockAlert $stockAlert): JsonResponse
    {
        $stockAlert->delete();

        return response()->json(['message' => 'Stock alert deleted.']);
    }
}
