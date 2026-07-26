<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transactions = InventoryTransaction::with('product:id,name_ar,name_en,sku', 'variant', 'changedBy:id,username')
            ->when($request->product_id, fn($q, $v) => $q->where('product_id', $v))
            ->when($request->variant_id, fn($q, $v) => $q->where('variant_id', $v))
            ->when($request->change_type, fn($q, $v) => $q->where('change_type', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id'      => 'required|exists:products,id',
            'variant_id'      => 'nullable|exists:product_variants,id',
            'quantity_change' => 'required|integer',
            'change_type'     => 'required|in:in,out,adjustment',
            'reason'          => 'nullable|string|max:255',
            'reference_id'    => 'nullable|string|max:100',
        ]);

        $data['changed_by'] = $request->user()->id;

        $transaction = DB::transaction(function () use ($data) {
            $transaction = InventoryTransaction::create($data);

            if (isset($data['variant_id'])) {
                $variant = \App\Models\ProductVariant::find($data['variant_id']);
                if ($variant) {
                    $variant->increment('stock_quantity', $data['quantity_change']);
                }
            } else {
                $product = \App\Models\Product::find($data['product_id']);
                if ($product) {
                    $product->increment('quantity_in_stock', $data['quantity_change']);
                }
            }

            return $transaction;
        });

        return response()->json([
            'message'     => 'Inventory transaction recorded.',
            'transaction' => $transaction->load('product', 'variant', 'changedBy'),
        ], 201);
    }

    public function show(InventoryTransaction $inventoryTransaction): JsonResponse
    {
        $inventoryTransaction->load('product', 'variant', 'changedBy');

        return response()->json(['transaction' => $inventoryTransaction]);
    }

    public function update(Request $request, InventoryTransaction $inventoryTransaction): JsonResponse
    {
        $data = $request->validate([
            'reason'       => 'nullable|string|max:255',
            'reference_id' => 'nullable|string|max:100',
        ]);

        $inventoryTransaction->update($data);

        return response()->json([
            'message'     => 'Inventory transaction updated.',
            'transaction' => $inventoryTransaction->fresh()->load('product', 'variant', 'changedBy'),
        ]);
    }

    public function destroy(InventoryTransaction $inventoryTransaction): JsonResponse
    {
        $inventoryTransaction->delete();

        return response()->json(['message' => 'Inventory transaction deleted.']);
    }
}
