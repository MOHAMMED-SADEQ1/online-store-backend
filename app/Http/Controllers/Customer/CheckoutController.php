<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerOrderResource;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PendingCheckout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CouponService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    public function paymentMethods(): JsonResponse
    {
        $methods = PaymentMethod::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn($m) => [
                'id'             => $m->id,
                'name_ar'        => $m->name_ar,
                'name_en'        => $m->name_en,
                'gateway'        => $m->gateway,
                'is_online'      => $m->is_online,
                'additional_fee' => (float) $m->additional_fee,
            ]);

        return response()->json([
            'payment_methods'   => $methods,
            'moyasar_key'       => config('moyasar.publishable_key'),
            'moyasar_test_mode' => config('moyasar.test_mode', true),
        ]);
    }

    /**
     * Get Moyasar payment gateway configuration for the frontend.
     */
    public function paymentConfig(): JsonResponse
    {
        return response()->json([
            'moyasar_key'       => config('moyasar.publishable_key'),
            'moyasar_test_mode' => config('moyasar.test_mode', true),
            'currency'          => 'SAR',
        ]);
    }

    public function pay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipping_address_id' => 'required|exists:addresses,id',
            'billing_address_id'  => 'sometimes|exists:addresses,id',
            'payment_method_id'   => 'required|exists:payment_methods,id',
            'notes'               => 'nullable|string|max:1000',
            'callback_url'        => 'nullable|url',
        ]);

        $user = $request->user();

        // Validate address ownership
        \App\Models\Address::where('user_id', $user->id)->findOrFail($data['shipping_address_id']);
        $billingAddressId = $data['billing_address_id'] ?? $data['shipping_address_id'];
        if ((int) $billingAddressId !== (int) $data['shipping_address_id']) {
            \App\Models\Address::where('user_id', $user->id)->findOrFail($billingAddressId);
        }

        // Get cart
        $cart = Cart::where('user_id', $user->id)->with('items.product', 'items.variant')->first();
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => __('order.cart_empty')], 422);
        }

        // Validate method
        $method = PaymentMethod::findOrFail($data['payment_method_id']);
        if (!$method->is_active) {
            return response()->json(['message' => __('payment.method_inactive')], 422);
        }

        // Validate token for online payments
        $isOnline = $method->gateway !== 'cod';
        if ($isOnline) {
            $request->validate(['token' => 'required|string']);
        }

        try {
            return DB::transaction(function () use ($data, $cart, $user, $method, $billingAddressId, $request) {
                // Calculate totals + validate stock
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

                        // Check for active flash sale on this variant
                        $flashSale = FlashSale::active()
                            ->where('product_id', $product->id)
                            ->where('variant_id', $variant->id)
                            ->first();
                        if ($flashSale) {
                            $unitPrice = $flashSale->flash_price;
                            // Check flash sale stock
                            $remaining = $flashSale->max_quantity - $flashSale->sold_quantity;
                            if ($remaining < $item->quantity) {
                                throw new \Exception(__('order.flash_sale_sold_out'));
                            }
                        }
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

                        // Check for active flash sale on product
                        $flashSale = FlashSale::active()
                            ->where('product_id', $product->id)
                            ->whereNull('variant_id')
                            ->first();
                        if ($flashSale) {
                            $unitPrice = $flashSale->flash_price;
                            $remaining = $flashSale->max_quantity - $flashSale->sold_quantity;
                            if ($remaining < $item->quantity) {
                                throw new \Exception(__('order.flash_sale_sold_out'));
                            }
                        }
                    }

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

                // Validate coupon
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

                // ===== COD: Create order immediately =====
                if ($method->gateway === 'cod') {
                    $orderNumber = 'ORD-' . strtoupper(uniqid());

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
                        'order_status'        => 'confirmed',
                        'payment_status'      => 'pending',
                        'payment_method_id'   => $method->id,
                        'callback_url'        => $data['callback_url'] ?? null,
                        'shipping_address_id' => $data['shipping_address_id'],
                        'billing_address_id'  => $billingAddressId,
                        'notes'               => $data['notes'] ?? null,
                        'confirmed_at'        => now(),
                    ]);
                    $order->save();

                    foreach ($itemsData as $item) {
                        $item['order_id'] = $order->id;
                        OrderItem::create($item);
                    }

                    // Create payment record
                    Payment::create([
                        'order_id'         => $order->id,
                        'method_id'        => $method->id,
                        'gateway'          => 'cod',
                        'payment_method'   => $method->name_ar ?? $method->name_en,
                        'transaction_id'   => 'COD-' . $orderNumber,
                        'amount'           => $finalTotal,
                        'payment_status'   => 'pending',
                        'paid_at'          => null,
                    ]);

                    // Decrement stock
                    foreach ($cart->items as $item) {
                        if ($item->variant_id) {
                            ProductVariant::where('id', $item->variant_id)
                                ->decrement('stock_quantity', $item->quantity);
                        } else {
                            Product::where('id', $item->product_id)
                                ->decrement('quantity_in_stock', $item->quantity);
                        }
                    }

                    // Record coupon usage
                    if ($couponCode) {
                        $coupon = Coupon::where('code', $couponCode)->first();
                        if ($coupon) {
                            $couponService->recordUsage($coupon, $order);
                        }
                    }

                    // Clear cart
                    $cart->items()->delete();
                    $cart->forceFill(['coupon_code' => null, 'coupon_discount' => 0])->save();

                    $order->load(['items', 'shippingAddress']);

                    return response()->json([
                        'message' => __('order.placed'),
                        'order'   => new CustomerOrderResource($order),
                    ], 201);
                }

                // ===== Online: Create PendingCheckout first =====
                $pendingCheckout = PendingCheckout::create([
                    'user_id'             => $user->id,
                    'cart_id'             => $cart->id,
                    'shipping_address_id' => $data['shipping_address_id'],
                    'billing_address_id'  => $billingAddressId,
                    'notes'               => $data['notes'] ?? null,
                    'payment_method_id'   => $method->id,
                    'callback_url'        => $data['callback_url'] ?? null,
                    'status'              => 'pending',
                    'cart_data'           => ['items' => $itemsData],
                    'total_amount'        => $total,
                    'discount_amount'     => $couponDiscount,
                    'final_amount'        => $finalTotal,
                    'coupon_code'         => $couponCode,
                    'expires_at'          => now()->addHours(2),
                ]);

                // Initiate payment with Moyasar
                $result = $this->paymentService->initiatePayment(
                    source: [
                        'id'          => $pendingCheckout->id,
                        'amount'      => $finalTotal,
                        'description' => 'Checkout #' . $pendingCheckout->id,
                        'metadata'    => [
                            'source_id' => (string) $pendingCheckout->id,
                            'user_id'   => (string) $user->id,
                        ],
                    ],
                    method: $method,
                    options: [
                        'token'        => $request->input('token'),
                        'callback_url' => $data['callback_url'] ?? null,
                    ]
                );

                // Update PendingCheckout with payment transaction info
                $pendingCheckout->update([
                    'transaction_id' => $result['payment_id'],
                    'payment_url'    => $result['payment_url'],
                ]);

                // If payment is immediately paid (no 3DS needed), create the order NOW
                if ($result['status'] === 'paid') {
                    $order = $this->createOrderFromCheckout($pendingCheckout, $result['payment_id'], $method);
                    $pendingCheckout->delete();

                    return response()->json([
                        'status'  => 'paid',
                        'order'   => new CustomerOrderResource($order),
                        'message' => __('payment.paid_successfully'),
                    ], 201);
                }

                // Otherwise (initiated/3DS needed), return payment_url
                return response()->json([
                    'checkout_id' => $pendingCheckout->id,
                    'payment_id'  => $result['payment_id'],
                    'payment_url' => $result['payment_url'],
                    'status'      => $result['status'],
                    'message'     => __('payment.redirect_to_3ds'),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function verify(Request $request, string $checkoutId): JsonResponse
    {
        $user = $request->user();

        $pendingCheckout = PendingCheckout::where('id', $checkoutId)
            ->where('user_id', $user->id)
            ->first();

        if (!$pendingCheckout) {
            // Maybe it was already processed (order created) - check orders
            $order = Order::where('user_id', $user->id)
                ->where('payment_method_id', $request->input('payment_method_id'))
                ->latest()
                ->first();

            if ($order && $order->payment_status === 'paid') {
                return response()->json([
                    'status'  => 'paid',
                    'message' => __('payment.paid_successfully'),
                    'order'   => new CustomerOrderResource($order),
                ]);
            }

            return response()->json([
                'status'  => 'no_checkout',
                'message' => __('payment.no_checkout'),
            ], 404);
        }

        if ($pendingCheckout->status === 'paid') {
            // Checkout completed, find the order
            $order = Order::where('user_id', $user->id)
                ->where('order_number', 'like', 'ORD-%')
                ->latest()
                ->first();

            return response()->json([
                'status'  => 'paid',
                'message' => __('payment.paid_successfully'),
                'order'   => $order ? new CustomerOrderResource($order) : null,
            ]);
        }

        if ($pendingCheckout->status === 'failed') {
            return response()->json([
                'status'  => 'failed',
                'message' => __('payment.failed'),
            ]);
        }

        // Still pending - check with Moyasar
        if ($pendingCheckout->transaction_id) {
            try {
                $result = $this->paymentService->verifyPayment($pendingCheckout->transaction_id, 'moyasar');
                $moyasarStatus = $result['status'] ?? 'unknown';

                if (in_array($moyasarStatus, ['paid', 'captured'])) {
                    $pendingCheckout->update(['status' => 'paid']);

                    // Create order now if webhook hasn't done it yet
                    $order = Order::where('user_id', $user->id)
                        ->where('payment_status', 'paid')
                        ->latest()
                        ->first();

                    if (!$order) {
                        $method = PaymentMethod::find($pendingCheckout->payment_method_id);
                        if ($method) {
                            $order = $this->createOrderFromCheckout(
                                $pendingCheckout,
                                $pendingCheckout->transaction_id,
                                $method
                            );
                            $pendingCheckout->delete();
                        }
                    }

                    return response()->json([
                        'status'  => 'paid',
                        'message' => __('payment.paid_successfully'),
                        'order'   => $order ? new CustomerOrderResource($order) : null,
                    ]);
                }

                if (in_array($moyasarStatus, ['failed', 'voided'])) {
                    $pendingCheckout->update(['status' => 'failed']);

                    return response()->json([
                        'status'  => 'failed',
                        'message' => __('payment.failed'),
                    ]);
                }

                return response()->json([
                    'status'  => 'pending',
                    'message' => __('payment.pending'),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status'  => 'pending',
                    'message' => __('payment.pending'),
                ]);
            }
        }

        return response()->json([
            'status'  => 'pending',
            'message' => __('payment.pending'),
        ]);
    }

    private function createOrderFromCheckout(PendingCheckout $checkout, string $transactionId, PaymentMethod $method): Order
    {
        $orderNumber = 'ORD-' . strtoupper(uniqid());
        $cartData = $checkout->cart_data;

        $order = new Order();
        $order->forceFill([
            'order_number'        => $orderNumber,
            'user_id'             => $checkout->user_id,
            'total_amount'        => $checkout->total_amount,
            'tax_amount'          => 0,
            'shipping_amount'     => 0,
            'discount_amount'     => $checkout->discount_amount,
            'coupon_code'         => $checkout->coupon_code,
            'final_amount'        => $checkout->final_amount,
            'order_status'        => 'confirmed',
            'payment_status'      => 'paid',
            'payment_method_id'   => $checkout->payment_method_id,
            'callback_url'        => $checkout->callback_url,
            'shipping_address_id' => $checkout->shipping_address_id,
            'billing_address_id'  => $checkout->billing_address_id,
            'notes'               => $checkout->notes,
            'confirmed_at'        => now(),
        ]);
        $order->save();

        // Create order items from cart_data snapshot
        foreach (($cartData['items'] ?? []) as $item) {
            OrderItem::create([
                'order_id'        => $order->id,
                'product_id'      => $item['product_id'],
                'variant_id'      => $item['variant_id'] ?? null,
                'quantity'        => $item['quantity'],
                'unit_price'      => $item['unit_price'],
                'subtotal'        => $item['subtotal'],
                'total_price'     => $item['total_price'] ?? $item['subtotal'],
                'product_name_ar' => $item['product_name_ar'] ?? '',
                'product_name_en' => $item['product_name_en'] ?? '',
            ]);
        }

        // Create payment record
        Payment::create([
            'order_id'         => $order->id,
            'method_id'        => $checkout->payment_method_id,
            'gateway'          => $method->gateway,
            'payment_method'   => $method->name_ar ?? $method->name_en,
            'transaction_id'   => $transactionId,
            'amount'           => $checkout->final_amount,
            'payment_status'   => 'completed',
            'gateway_response' => [],
            'callback_url'     => $checkout->callback_url,
            'paid_at'          => now(),
        ]);

        // Decrement stock
        foreach (($cartData['items'] ?? []) as $item) {
            if ($item['variant_id'] ?? null) {
                ProductVariant::where('id', $item['variant_id'])
                    ->decrement('stock_quantity', $item['quantity']);
            } else {
                Product::where('id', $item['product_id'])
                    ->decrement('quantity_in_stock', $item['quantity']);
            }
        }

        // Record coupon usage
        if ($checkout->coupon_code) {
            $coupon = Coupon::where('code', $checkout->coupon_code)->first();
            if ($coupon) {
                $couponService = app(CouponService::class);
                $couponService->recordUsage($coupon, $order);
            }
        }

        // Clear cart
        $cart = $checkout->cart;
        if ($cart) {
            $cart->items()->delete();
            $cart->forceFill(['coupon_code' => null, 'coupon_discount' => 0])->save();
        }

        $order->load(['items', 'shippingAddress']);

        return $order;
    }
}
