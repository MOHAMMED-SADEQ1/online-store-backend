<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected const STATUS_TIMESTAMPS = [
        'confirmed' => 'confirmed_at',
        'processing'=> 'processing_at',
        'shipped'   => 'shipped_at',
        'delivered' => 'delivered_at',
        'cancelled' => 'cancelled_at',
    ];

    public function getOrders(array $filters): LengthAwarePaginator
    {
        return Order::with('user:id,username,email,first_name,last_name')
            ->when($filters['order_status'] ?? null, fn($q, $v) => $q->where('order_status', $v))
            ->when($filters['payment_status'] ?? null, fn($q, $v) => $q->where('payment_status', $v))
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('order_number', 'like', "%{$v}%"))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', Carbon::parse($v)))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', Carbon::parse($v)))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['order'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getOrder(Order $order): Order
    {
        return $order->load([
            'user',
            'items.product.images',
            'items.product.categories',
            'items.variant.attributeValues.attribute',
            'items.variant.images',
            'payments.method',
            'shipping.shippingZone',
            'shippingAddress',
            'billingAddress',
        ]);
    }

    public function updateStatus(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $updates = [];

            if (isset($data['order_status'])) {
                $updates['order_status'] = $data['order_status'];
                $timestampField = self::STATUS_TIMESTAMPS[$data['order_status']] ?? null;
                if ($timestampField && is_null($order->{$timestampField})) {
                    $updates[$timestampField] = now();
                }
            }

            if (isset($data['payment_status'])) {
                $updates['payment_status'] = $data['payment_status'];
            }

            $order->forceFill($updates)->save();

            return $order->fresh()->load([
                'user',
                'items.product.images',
                'items.product.categories',
                'items.variant.attributeValues.attribute',
                'items.variant.images',
                'payments.method',
                'shipping.shippingZone',
                'shippingAddress',
                'billingAddress',
            ]);
        });
    }

    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->payments()->delete();
            $order->shipping()?->delete();
            $order->delete();
        });
    }

    public function getRevenueSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Order::where('payment_status', 'paid');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return [
            'total_revenue'  => (float) $query->sum('final_amount'),
            'total_orders'   => (int) $query->count(),
            'avg_order_value' => (float) $query->avg('final_amount') ?? 0,
        ];
    }

    public function getOrdersByStatus(): array
    {
        return Order::selectRaw('order_status, COUNT(*) as count')
            ->groupBy('order_status')
            ->pluck('count', 'order_status')
            ->toArray();
    }
}
