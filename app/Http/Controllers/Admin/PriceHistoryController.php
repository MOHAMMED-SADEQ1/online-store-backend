<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $history = PriceHistory::with('product:id,name_ar,name_en,sku', 'variant', 'changedBy:id,username,email')
            ->when($request->product_id, fn($q, $v) => $q->where('product_id', $v))
            ->when($request->variant_id, fn($q, $v) => $q->where('variant_id', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($history);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'old_price'  => 'required|numeric|min:0',
            'new_price'  => 'required|numeric|min:0',
            'note'       => 'nullable|string|max:255',
        ]);

        $data['changed_by'] = $request->user()->id;

        $history = PriceHistory::create($data);

        return response()->json([
            'message' => 'Price history recorded.',
            'history' => $history->load('product', 'variant', 'changedBy'),
        ], 201);
    }

    public function show(PriceHistory $priceHistory): JsonResponse
    {
        $priceHistory->load('product', 'variant', 'changedBy');

        return response()->json(['history' => $priceHistory]);
    }

    public function destroy(PriceHistory $priceHistory): JsonResponse
    {
        $priceHistory->delete();

        return response()->json(['message' => 'Price history deleted.']);
    }
}
