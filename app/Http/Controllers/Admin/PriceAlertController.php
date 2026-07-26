<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceAlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alerts = PriceAlert::with('user:id,username,email', 'product', 'variant')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active))
            ->when($request->is_triggered !== null, fn($q) => $q->where('is_triggered', $request->is_triggered))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($alerts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'product_id'   => 'required|exists:products,id',
            'variant_id'   => 'nullable|exists:product_variants,id',
            'target_price' => 'required|numeric|min:0',
            'is_active'    => 'boolean',
        ]);

        $alert = PriceAlert::create($data);

        return response()->json([
            'message' => 'Price alert created.',
            'alert'   => $alert->load('user', 'product', 'variant'),
        ], 201);
    }

    public function show(PriceAlert $priceAlert): JsonResponse
    {
        $priceAlert->load('user', 'product', 'variant');

        return response()->json(['alert' => $priceAlert]);
    }

    public function update(Request $request, PriceAlert $priceAlert): JsonResponse
    {
        $data = $request->validate([
            'target_price' => 'sometimes|numeric|min:0',
            'is_active'    => 'boolean',
            'is_triggered' => 'boolean',
            'triggered_at' => 'nullable|date',
        ]);

        $priceAlert->update($data);

        return response()->json([
            'message' => 'Price alert updated.',
            'alert'   => $priceAlert->fresh()->load('user', 'product', 'variant'),
        ]);
    }

    public function destroy(PriceAlert $priceAlert): JsonResponse
    {
        $priceAlert->delete();

        return response()->json(['message' => 'Price alert deleted.']);
    }
}
