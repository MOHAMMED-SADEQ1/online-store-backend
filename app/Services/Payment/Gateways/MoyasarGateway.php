<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment as PaymentModel;
use App\Models\PendingCheckout;
use App\Models\PaymentMethod;
use App\Services\Payment\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyasarGateway implements PaymentGateway
{
    private function baseUrl(): string
    {
        return config('moyasar.base_url', 'https://api.moyasar.com/v1');
    }

    private function secretKey(): string
    {
        return config('moyasar.secret_key');
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth($this->secretKey(), '')
            ->withHeaders(['Content-Type' => 'application/json']);
    }

    public function createPayment(array $source, PaymentMethod $method, array $options = []): array
    {
        $token = $options['token'] ?? null;
        $callbackUrl = $options['callback_url'] ?? null;

        if (!$token) {
            throw new \InvalidArgumentException('Moyasar payment requires a token from Moyasar.js');
        }

        $sourceId = $source['id'] ?? 0;
        $description = $source['description'] ?? 'Checkout #' . $sourceId;
        $meta = $source['metadata'] ?? [];
        $meta['source_id'] = (string) $sourceId;

        $payload = [
            'amount'       => (int) (($source['amount'] ?? 0) * 100), // SAR → Halala
            'currency'     => 'SAR',
            'description'  => $description,
            'source'       => [
                'type'  => 'token',
                'token' => $token,
            ],
            'callback_url' => $callbackUrl,
            // 3ds is NOT set here — Moyasar determines it from token status:
            // active token → 3ds=false (already authenticated)
            // save_only token → 3ds=true (needs 3DS)
            'metadata'     => $meta,
        ];

        Log::info('Moyasar: Creating payment', ['source_id' => $sourceId, 'amount' => $payload['amount']]);

        $response = $this->client()->post("{$this->baseUrl()}/payments", $payload);

        if ($response->failed()) {
            Log::error('Moyasar: Payment creation failed', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
            throw new \Exception('Moyasar payment failed: ' . ($response->json()['message'] ?? $response->body()));
        }

        $payment = $response->json();
        Log::info('Moyasar: Payment created', ['id' => $payment['id'], 'status' => $payment['status']]);

        $result = [
            'payment_id'       => $payment['id'],
            'status'           => $payment['status'],
            'gateway_response' => $payment,
        ];

        if ($payment['status'] === 'initiated' && isset($payment['source']['transaction_url'])) {
            $result['payment_url'] = $payment['source']['transaction_url'];
        } elseif ($payment['status'] === 'paid') {
            $result['payment_url'] = null;
        }

        return $result;
    }

    public function verify(string $transactionId): array
    {
        Log::info('Moyasar: Verifying payment', ['id' => $transactionId]);

        $response = $this->client()->get("{$this->baseUrl()}/payments/{$transactionId}");

        if ($response->failed()) {
            Log::error('Moyasar: Payment verification failed', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
            throw new \Exception('Moyasar verification failed: ' . ($response->json()['message'] ?? $response->body()));
        }

        return $response->json();
    }

    public function handleWebhook(array $payload): void
    {
        Log::info('Moyasar: Webhook received', ['type' => $payload['type'] ?? 'unknown']);

        if (($payload['type'] ?? '') !== 'payment_status') {
            Log::warning('Moyasar: Ignoring unsupported webhook type', ['type' => $payload['type'] ?? null]);
            return;
        }

        $data = $payload['data'] ?? [];
        $paymentId = $data['id'] ?? null;
        $status = $data['status'] ?? null;

        if (!$paymentId || !$status) {
            Log::warning('Moyasar: Invalid webhook payload', ['payload' => $payload]);
            return;
        }

        $ourPaymentStatus = match ($status) {
            'paid', 'captured' => 'completed',
            'failed', 'voided' => 'failed',
            'refunded'         => 'refunded',
            default            => 'pending',
        };

        // Try to find existing Payment record (old flow - pre-existing order)
        $payment = PaymentModel::where('transaction_id', $paymentId)->first();

        if ($payment) {
            // Old flow: order already exists, just update
            $payment->update([
                'payment_status'   => $ourPaymentStatus,
                'gateway_response' => $data,
                'paid_at'          => in_array($status, ['paid', 'captured']) ? now() : $payment->paid_at,
            ]);

            $order = $payment->order;
            if ($order) {
                $this->updateOrderStatus($order, $payment, $status);
            }

            Log::info('Moyasar: Existing payment updated', ['transaction_id' => $paymentId, 'status' => $status]);
            return;
        }

        // New flow: find PendingCheckout via metadata.source_id
        $sourceId = $data['metadata']['source_id'] ?? null;
        if (!$sourceId) {
            Log::warning('Moyasar: No source_id in metadata and no Payment record found', ['transaction_id' => $paymentId]);
            return;
        }

        $pendingCheckout = PendingCheckout::find($sourceId);
        if (!$pendingCheckout) {
            Log::warning('Moyasar: PendingCheckout not found', ['source_id' => $sourceId]);
            return;
        }

        if (in_array($status, ['paid', 'captured'])) {
            DB::transaction(function () use ($pendingCheckout, $paymentId, $data, $status, $ourPaymentStatus) {
                // Create payment method description
                $methodDesc = $pendingCheckout->paymentMethod->name_ar ?? $pendingCheckout->paymentMethod->name_en ?? 'Online';

                // Create order from pending checkout
                $orderNumber = 'ORD-' . strtoupper(uniqid());

                $order = new \App\Models\Order();
                $order->forceFill([
                    'order_number'        => $orderNumber,
                    'user_id'             => $pendingCheckout->user_id,
                    'total_amount'        => $pendingCheckout->total_amount,
                    'tax_amount'          => 0,
                    'shipping_amount'     => 0,
                    'discount_amount'     => $pendingCheckout->discount_amount,
                    'coupon_code'         => $pendingCheckout->coupon_code,
                    'final_amount'        => $pendingCheckout->final_amount,
                    'order_status'        => 'confirmed',
                    'payment_status'      => 'paid',
                    'payment_method_id'   => $pendingCheckout->payment_method_id,
                    'callback_url'        => $pendingCheckout->callback_url,
                    'shipping_address_id' => $pendingCheckout->shipping_address_id,
                    'billing_address_id'  => $pendingCheckout->billing_address_id,
                    'notes'               => $pendingCheckout->notes,
                    'confirmed_at'        => now(),
                ]);
                $order->save();

                // Create order items from cart_data snapshot
                $cartData = $pendingCheckout->cart_data;
                foreach (($cartData['items'] ?? []) as $item) {
                    \App\Models\OrderItem::create([
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
                \App\Models\Payment::create([
                    'order_id'         => $order->id,
                    'method_id'        => $pendingCheckout->payment_method_id,
                    'gateway'          => 'moyasar',
                    'payment_method'   => $methodDesc,
                    'transaction_id'   => $paymentId,
                    'amount'           => $pendingCheckout->final_amount,
                    'payment_status'   => $ourPaymentStatus,
                    'gateway_response' => $data,
                    'callback_url'     => $pendingCheckout->callback_url,
                    'paid_at'          => now(),
                ]);

                // Decrement stock
                foreach (($cartData['items'] ?? []) as $item) {
                    if ($item['variant_id'] ?? null) {
                        \App\Models\ProductVariant::where('id', $item['variant_id'])
                            ->decrement('stock_quantity', $item['quantity']);
                    } else {
                        \App\Models\Product::where('id', $item['product_id'])
                            ->decrement('quantity_in_stock', $item['quantity']);
                    }
                }

                // Record coupon usage if applicable
                if ($pendingCheckout->coupon_code) {
                    $coupon = \App\Models\Coupon::where('code', $pendingCheckout->coupon_code)->first();
                    if ($coupon) {
                        $couponService = app(\App\Services\CouponService::class);
                        $couponService->recordUsage($coupon, $order);
                    }
                }

                // Clear cart
                $cart = $pendingCheckout->cart;
                if ($cart) {
                    $cart->items()->delete();
                    $cart->forceFill(['coupon_code' => null, 'coupon_discount' => 0])->save();
                }

                // Delete pending checkout
                $pendingCheckout->delete();

                Log::info('Moyasar: Order created from pending checkout', [
                    'order_id' => $order->id,
                    'transaction_id' => $paymentId,
                ]);
            });
        } else {
            // Payment failed or refunded
            $pendingCheckout->update([
                'status' => $ourPaymentStatus === 'refunded' ? 'paid' : 'failed',
            ]);

            Log::info('Moyasar: PendingCheckout marked as failed', [
                'checkout_id' => $pendingCheckout->id,
                'status' => $status,
            ]);
        }
    }

    private function updateOrderStatus(\App\Models\Order $order, PaymentModel $payment, string $moyasarStatus): void
    {
        if (in_array($moyasarStatus, ['paid', 'captured'])) {
            $order->update([
                'payment_status' => 'paid',
                'order_status'   => 'confirmed',
                'confirmed_at'   => now(),
            ]);
        } elseif ($moyasarStatus === 'failed') {
            $order->update(['payment_status' => 'failed']);
        } elseif ($moyasarStatus === 'refunded') {
            $order->update(['payment_status' => 'refunded']);
        }
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        $payload = [];
        if ($amount !== null) {
            $payload['amount'] = (int) ($amount * 100);
        }

        Log::info('Moyasar: Refunding payment', ['id' => $transactionId, 'amount' => $amount]);

        $response = $this->client()->post("{$this->baseUrl()}/payments/{$transactionId}/refund", $payload);

        if ($response->failed()) {
            Log::error('Moyasar: Refund failed', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
            throw new \Exception('Moyasar refund failed: ' . ($response->json()['message'] ?? $response->body()));
        }

        return $response->json();
    }
}
