<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerOrderResource;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CouponService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipping_address_id' => 'required|exists:addresses,id',
            'billing_address_id'  => 'sometimes|exists:addresses,id',
            'payment_method_id'   => 'sometimes|exists:payment_methods,id',
            'callback_url'        => 'nullable|url',
            'notes'               => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // Fix #1: Address ownership check
        Address::where('user_id', $user->id)->findOrFail($data['shipping_address_id']);
        $billingAddressId = $data['billing_address_id'] ?? $data['shipping_address_id'];
        if ((int) $billingAddressId !== (int) $data['shipping_address_id']) {
            Address::where('user_id', $user->id)->findOrFail($billingAddressId);
        }

        $cart = Cart::where('user_id', $user->id)->with('items')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => __('order.cart_empty')], 422);
        }

        try {
            $order = DB::transaction(function () use ($data, $cart, $user, $billingAddressId) {
                $itemsData = [];
                $total = 0;
                $productQuantities = [];

                foreach ($cart->items as $item) {
                    if ($item->variant_id) {
                        $variant = ProductVariant::where('id', $item->variant_id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        if (!$variant->is_active) {
                            throw new \Exception(__('order.variant_unavailable'));
                        }
                        if ($variant->stock_quantity < $item->quantity) {
                            throw new \Exception(__('order.stock_insufficient_variant'));
                        }

                        $product = $variant->product;
                        $unitPrice = $variant->sale_price ?? $variant->regular_price;
                    } else {
                        $product = Product::where('id', $item->product_id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        if (!$product->is_active) {
                            throw new \Exception(__('order.product_unavailable'));
                        }
                        if ($product->quantity_in_stock < $item->quantity) {
                            throw new \Exception(__('order.stock_insufficient_product'));
                        }

                        $unitPrice = $product->sale_price ?? $product->regular_price;
                    }

                    // Fix #8: max_per_order check
                    if ($product->max_per_order && $product->max_per_order > 0) {
                        $currentQty = ($productQuantities[$product->id] ?? 0) + $item->quantity;
                        if ($currentQty > $product->max_per_order) {
                            throw new \Exception(__('order.max_per_order', ['max' => $product->max_per_order]));
                        }
                        $productQuantities[$product->id] = $currentQty;
                    }

                    $subtotal = $unitPrice * $item->quantity;
                    $total += $subtotal;

                    $itemsData[] = [
                        'product_id'      => $item->product_id,
                        'variant_id'      => $item->variant_id,
                        'quantity'        => $item->quantity,
                        'unit_price'      => $unitPrice,
                        'subtotal'        => $subtotal,
                        'total_price'     => $subtotal,
                        'product_name_ar' => $product->name_ar ?? '',
                        'product_name_en' => $product->name_en ?? '',
                    ];
                }

                // Fix #3: Re-validate coupon from DB
                $couponDiscount = 0;
                $couponCode = null;
                if ($cart->coupon_code) {
                    $couponService = app(CouponService::class);
                    $cartItemsForValidation = $cart->items->map(fn($i) => [
                        'product_id' => $i->product_id,
                        'variant_id' => $i->variant_id,
                        'quantity'   => $i->quantity,
                    ])->toArray();

                    $result = $couponService->validate(
                        $cart->coupon_code,
                        $total,
                        $user->id,
                        $cartItemsForValidation,
                    );

                    if ($result['valid']) {
                        $couponDiscount = $result['discount_amount'];
                        $couponCode = $cart->coupon_code;
                    }
                }

                $finalTotal = max(0, $total - $couponDiscount);
                $orderNumber = 'ORD-' . strtoupper(uniqid());

                // Fix #6: Use forceFill for financial fields (removed from $fillable)
                $order = new Order();
                $order->forceFill([
                    'order_number'        => $orderNumber,
                    'user_id'             => $user->id,
                    'total_amount'        => $total,
                    'tax_amount'          => 0,
                    'shipping_amount'     => 0,
                    'discount_amount'     => $couponDiscount,
                    'coupon_code'         => $couponCode,
                    'final_amount'        => $finalTotal,
                    'order_status'        => 'pending',
                    'payment_status'      => 'pending',
                    'payment_method_id'   => $data['payment_method_id'] ?? null,
                    'callback_url'        => $data['callback_url'] ?? null,
                    'shipping_address_id' => $data['shipping_address_id'],
                    'billing_address_id'  => $billingAddressId,
                    'notes'               => $data['notes'] ?? null,
                ]);
                $order->save();

                foreach ($itemsData as $item) {
                    $item['order_id'] = $order->id;
                    OrderItem::create($item);
                }

                // Fix #2: Decrement stock
                foreach ($cart->items as $item) {
                    if ($item->variant_id) {
                        ProductVariant::where('id', $item->variant_id)
                            ->decrement('stock_quantity', $item->quantity);
                    } else {
                        Product::where('id', $item->product_id)
                            ->decrement('quantity_in_stock', $item->quantity);
                    }
                }

                // Fix #3: Record coupon usage (including free-shipping coupons with $0 discount)
                if ($couponCode) {
                    $coupon = Coupon::where('code', $couponCode)->first();
                    if ($coupon) {
                        $couponService->recordUsage($coupon, $order);
                    }
                }

                // Clear cart
                $cart->items()->delete();
                $cart->forceFill(['coupon_code' => null, 'coupon_discount' => 0])->save();

                return $order;
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $order->load(['items', 'shippingAddress']);

        return response()->json([
            'message' => __('order.placed'),
            'order'   => new CustomerOrderResource($order),
        ], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => __('order.unauthorized')], 403);
        }

        $order->load(['items.product', 'shippingAddress', 'shipping']);

        return response()->json([
            'order' => new CustomerOrderResource($order),
        ]);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => __('order.unauthorized')], 403);
        }

        $cancellableStatuses = ['pending', 'confirmed', 'processing'];
        if (!in_array($order->order_status, $cancellableStatuses)) {
            return response()->json(['message' => __('order.cannot_cancel')], 422);
        }

        // Check each product in the order is cancellable
        $order->load('items.product');
        foreach ($order->items as $item) {
            if ($item->product && !$item->product->is_cancellable) {
                return response()->json([
                    'message' => __('order.not_cancellable', [
                        'name' => $item->product->{'name_' . app()->getLocale()},
                    ]),
                ], 422);
            }
        }

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($order, $data) {
                $refunded = false;

                // If order was paid, refund via payment gateway
                if ($order->payment_status === 'paid') {
                    $payment = $order->payments()->where('payment_status', 'completed')->first();
                    if ($payment && $payment->gateway !== 'cod') {
                        try {
                            $paymentService = app(PaymentService::class);
                            $paymentService->refund($payment->transaction_id, $order->final_amount, $payment->gateway);
                            $refunded = true;
                        } catch (\Exception $e) {
                            \Log::warning('Order cancellation refund failed', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                // Restore stock
                $order->load('items');
                foreach ($order->items as $item) {
                    if ($item->variant_id) {
                        ProductVariant::where('id', $item->variant_id)
                            ->increment('stock_quantity', $item->quantity);
                    } else {
                        Product::where('id', $item->product_id)
                            ->increment('quantity_in_stock', $item->quantity);
                    }
                }

                $order->forceFill([
                    'order_status'   => 'cancelled',
                    'payment_status' => $refunded ? 'refunded' : ($order->payment_status === 'paid' ? 'refunded' : 'pending'),
                    'cancelled_at'   => now(),
                    'cancel_reason'  => $data['reason'] ?? null,
                ]);
                $order->save();
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $order->load(['items', 'shippingAddress']);

        return response()->json([
            'message' => __('order.cancelled'),
            'order'   => new CustomerOrderResource($order),
        ]);
    }

    public function tracking(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => __('order.unauthorized')], 403);
        }

        if ($order->order_status === 'cancelled') {
            return response()->json([
                'current_status'         => 'cancelled',
                'estimated_delivery_date'=> null,
                'cancelled_at'           => $order->cancelled_at?->toIso8601String(),
                'cancel_reason'          => $order->cancel_reason,
                'timeline'               => [
                    [
                        'status'       => 'pending',
                        'label_ar'     => 'قيد الانتظار',
                        'label_en'     => 'Pending',
                        'timestamp'    => $order->created_at?->toIso8601String(),
                        'is_completed' => true,
                    ],
                    [
                        'status'       => 'cancelled',
                        'label_ar'     => 'ملغي',
                        'label_en'     => 'Cancelled',
                        'timestamp'    => $order->cancelled_at?->toIso8601String(),
                        'is_completed' => true,
                    ],
                ],
            ]);
        }

        $shipping = $order->shipping;

        $timeline = [];

        $statuses = [
            'pending'   => ['label_ar' => 'قيد الانتظار', 'label_en' => 'Pending'],
            'confirmed' => ['label_ar' => 'تم التأكيد', 'label_en' => 'Confirmed'],
            'processing'=> ['label_ar' => 'قيد التجهيز', 'label_en' => 'Processing'],
            'shipped'   => ['label_ar' => 'تم الشحن', 'label_en' => 'Shipped'],
            'delivered' => ['label_ar' => 'تم التوصيل', 'label_en' => 'Delivered'],
        ];

        $statusTimestamps = [
            'pending'   => $order->created_at,
            'confirmed' => $order->confirmed_at,
            'processing'=> $order->processing_at ?? $order->confirmed_at,
            'shipped'   => $order->shipped_at,
            'delivered' => $order->delivered_at,
        ];

        $statusOrder = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
        $currentIndex = array_search($order->order_status, $statusOrder);

        foreach ($statusOrder as $i => $status) {
            $info = $statuses[$status];
            $timestamp = $statusTimestamps[$status];

            $isCompleted = $i <= $currentIndex && $timestamp !== null;

            if ($status === 'pending' || $timestamp !== null || $i <= $currentIndex) {
                $timeline[] = [
                    'status'       => $status,
                    'label_ar'     => $info['label_ar'],
                    'label_en'     => $info['label_en'],
                    'description'  => $isCompleted ? '' : '',
                    'timestamp'    => $timestamp?->toIso8601String(),
                    'is_completed' => $isCompleted,
                ];
            }
        }

        if ($shipping && $shipping->tracking_number) {
            $timeline[] = [
                'status'       => $shipping->shipping_status,
                'label_ar'     => match($shipping->shipping_status) {
                    'shipped' => 'تم الشحن',
                    'in_transit' => 'في الطريق',
                    'out_for_delivery' => 'قيد التوصيل',
                    'delivered' => 'تم التوصيل',
                    default => $shipping->shipping_status,
                },
                'label_en'     => $shipping->shipping_status,
                'description'  => "Carrier: {$shipping->carrier} / Track: {$shipping->tracking_number}",
                'timestamp'    => $shipping->shipping_date?->toIso8601String(),
                'is_completed' => in_array($shipping->shipping_status, ['delivered', 'out_for_delivery']),
            ];
        }

        return response()->json([
            'current_status'         => $order->order_status,
            'estimated_delivery_date'=> $shipping?->estimated_delivery?->toIso8601String(),
            'timeline'               => $timeline,
        ]);
    }
}
