<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\ReturnItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function index(Order $order): JsonResponse
    {
        $this->authorizeAccess($order);

        $returns = $order->returnRequests()->with('items.product', 'items.orderItem')->get()->map(fn($r) => [
            'id'          => $r->id,
            'order_id'    => $r->order_id,
            'return_type' => $r->return_type,
            'status'      => $r->status,
            'notes'       => $r->notes,
            'created_at'  => $r->created_at,
            'items'       => $r->items->map(fn($i) => [
                'order_item_id' => $i->order_item_id,
                'product_name'  => $i->product->name_ar ?? '',
                'quantity'      => $i->quantity,
                'reason'        => $i->reason,
                'status'        => $r->status,
            ]),
        ]);

        return response()->json(['returns' => $returns]);
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $this->authorizeAccess($order);

        if ($order->order_status !== 'delivered') {
            return response()->json(['message' => __('return.only_delivered')], 422);
        }

        $data = $request->validate([
            'items'       => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.reason'        => 'nullable|string|max:500',
            'return_type' => 'required|in:refund,exchange',
            'notes'       => 'nullable|string|max:1000',
            'exchange_items' => 'required_if:return_type,exchange|array',
            'exchange_items.*.product_id' => 'required_with:exchange_items|exists:products,id',
            'exchange_items.*.variant_id' => 'nullable|exists:product_variants,id',
            'exchange_items.*.quantity'   => 'required_with:exchange_items|integer|min:1',
        ]);

        $deliveredAt = $order->delivered_at ?? $order->updated_at;
        $daysSinceDelivery = $deliveredAt->diffInDays(now());

        $preparedItems = [];
        foreach ($data['items'] as $item) {
            $orderItem = $order->items()->with('product')->findOrFail($item['order_item_id']);
            $product = $orderItem->product;

            // Check if product is returnable
            if (!$product->is_returnable) {
                return response()->json([
                    'message' => __('return.not_returnable', ['name' => $product->{'name_' . app()->getLocale()}]),
                ], 422);
            }

            // Check if product is exchangeable (for exchange requests)
            if ($data['return_type'] === 'exchange' && !$product->is_exchangeable) {
                return response()->json([
                    'message' => __('return.not_exchangeable', ['name' => $product->{'name_' . app()->getLocale()}]),
                ], 422);
            }

            // Check return period
            if ($daysSinceDelivery > $product->return_period_days) {
                return response()->json([
                    'message' => __('return.period_expired', [
                        'name' => $product->{'name_' . app()->getLocale()},
                        'days' => $product->return_period_days,
                    ]),
                ], 422);
            }

            $alreadyReturned = (int) ReturnItem::whereHas('returnRequest', fn($q) => $q->where('order_id', $order->id))
                ->where('order_item_id', $orderItem->id)
                ->sum('quantity');

            $returnableQty = $orderItem->quantity - $alreadyReturned;

            if ($item['quantity'] > $returnableQty) {
            return response()->json([
                'message' => __('return.quantity_exceeds', ['max' => $returnableQty]),
            ], 422);
            }

            $preparedItems[] = [
                'order_item_id' => $orderItem->id,
                'product_id'    => $orderItem->product_id,
                'quantity'      => $item['quantity'],
                'reason'        => $item['reason'] ?? null,
            ];
        }

        $return = ReturnRequest::create([
            'order_id'      => $order->id,
            'user_id'       => $request->user()->id,
            'return_type'   => $data['return_type'],
            'status'        => 'pending',
            'notes'         => $data['notes'] ?? null,
            'exchange_items'=> $data['exchange_items'] ?? null,
        ]);

        foreach ($preparedItems as $prepared) {
            ReturnItem::create(array_merge($prepared, ['return_request_id' => $return->id]));
        }

        $return->load('items.product', 'items.orderItem');

        return response()->json([
            'message' => __('return.submitted'),
            'return'  => [
                'id'          => $return->id,
                'order_id'    => $return->order_id,
                'return_type' => $return->return_type,
                'status'      => $return->status,
                'notes'       => $return->notes,
                'created_at'  => $return->created_at,
                'items'       => $return->items->map(fn($i) => [
                    'order_item_id' => $i->order_item_id,
                    'product_name'  => $i->product->name_ar ?? '',
                    'quantity'      => $i->quantity,
                    'reason'        => $i->reason,
                    'status'        => $return->status,
                ]),
            ],
        ], 201);
    }

    public function policy(): JsonResponse
    {
        $locale = app()->getLocale();
        $defaultDays = 14;

        return response()->json([
            'policy_text_ar'   => 'يمكن إرجاع المنتجات خلال 14 يوم من تاريخ الاستلام. يجب أن تكون المنتجات في حالتها الأصلية.',
            'policy_text_en'   => 'Products can be returned within 14 days of receipt. Items must be in original condition.',
            'default_days'     => $defaultDays,
            'conditions'       => [
                'المنتج في حالته الأصلية',
                'العبوة الأصلية سليمة',
                'لم يتم استخدام المنتج',
                'يجب إرفاق فاتورة الشراء',
            ],
            'note'             => 'قد تختلف مدة الإرجاع حسب المنتج. راجع صفحة المنتج للحصول على التفاصيل الدقيقة.',
        ]);
    }

    private function authorizeAccess(Order $order): void
    {
        if ($order->user_id !== request()->user()->id) {
            abort(403, __('order.unauthorized'));
        }
    }
}
