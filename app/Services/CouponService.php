<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CouponService
{
    public function validate(string $code, float $subtotal, ?int $userId = null, ?array $cartItems = null): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return ['valid' => false, 'message' => __('coupon.invalid')];
        }

        $result = $this->validateCoupon($coupon, $userId, $cartItems, $subtotal);
        if (!$result['valid']) {
            return $result;
        }

        $discount = $this->calculateDiscount($coupon, $subtotal, $cartItems);

        return [
            'valid'           => true,
            'coupon'          => $this->getFormattedCoupon($coupon),
            'discount_amount' => $discount['discount'],
            'is_free_shipping'=> $coupon->is_free_shipping,
        ];
    }

    public function validateCoupon(Coupon $coupon, ?int $userId = null, ?array $cartItems = null, ?float $subtotal = null): array
    {
        if (!$coupon->is_active) {
            return ['valid' => false, 'message' => __('coupon.deactivated')];
        }

        $now = Carbon::now();

        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            return ['valid' => false, 'message' => __('coupon.not_active_yet')];
        }

        if ($coupon->end_date && $now->gt($coupon->end_date)) {
            return ['valid' => false, 'message' => __('coupon.expired')];
        }

        if ($coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) {
            return ['valid' => false, 'message' => __('coupon.limit_reached')];
        }

        // Coupon specific to one user
        if ($coupon->user_id && $coupon->user_id !== $userId) {
            return ['valid' => false, 'message' => __('coupon.not_valid_for_user')];
        }

        // Per-user limit
        if ($coupon->per_user_limit && $userId) {
            $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $userId)
                ->count();

            if ($userUsageCount >= $coupon->per_user_limit) {
                return ['valid' => false, 'message' => __('coupon.per_user_limit_reached')];
            }
        }

        // Minimum orders count (repeat purchase)
        if ($coupon->min_orders_count && $userId) {
            $completedOrders = Order::where('user_id', $userId)
                ->whereIn('order_status', ['delivered', 'completed'])
                ->count();

            if ($completedOrders < $coupon->min_orders_count) {
                return ['valid' => false, 'message' => __('coupon.min_orders', ['count' => $coupon->min_orders_count])];
            }
        }

        if ($subtotal !== null && $coupon->minimum_order_amount > 0 && $subtotal < $coupon->minimum_order_amount) {
            return ['valid' => false, 'message' => __('coupon.min_amount', ['amount' => $coupon->minimum_order_amount])];
        }

        // Applicable-to validation
        if ($coupon->applicable_to !== 'all' && $cartItems) {
            $valid = $this->validateApplicableItems($coupon, $cartItems);
            if (!$valid) {
                return ['valid' => false, 'message' => __('coupon.not_applicable')];
            }
        }

        return ['valid' => true];
    }

    public function calculateDiscount(Coupon $coupon, float $subtotal, ?array $cartItems = null): array
    {
        if ($coupon->applicable_to !== 'all' && $cartItems) {
            $applicable = $this->getApplicableSubtotal($coupon, $cartItems);
            $subtotal = $applicable['subtotal'];
        }

        if ($coupon->is_free_shipping) {
            return [
                'discount' => 0,
                'subtotal_applicable' => round($subtotal, 2),
            ];
        }

        $discount = $coupon->discount_type === 'percentage'
            ? ($subtotal * $coupon->discount_value / 100)
            : $coupon->discount_value;

        $maxDiscount = (float) $coupon->maximum_discount;
        if ($maxDiscount > 0 && $discount > $maxDiscount) {
            $discount = $maxDiscount;
        }

        return [
            'discount' => round($discount, 2),
            'subtotal_applicable' => round($subtotal, 2),
        ];
    }

    public function recordUsage(Coupon $coupon, Order $order): void
    {
        // Lock coupon row to prevent race condition on used_count
        $lockedCoupon = Coupon::where('id', $coupon->id)->lockForUpdate()->first();

        if ($lockedCoupon->usage_limit > 0 && $lockedCoupon->used_count >= $lockedCoupon->usage_limit) {
            throw new \Exception(__('order.coupon_limit_reached'));
        }

        CouponUsage::create([
            'coupon_id'       => $coupon->id,
            'order_id'        => $order->id,
            'user_id'         => $order->user_id,
            'discount_amount' => $order->discount_amount,
            'used_at'         => now(),
        ]);

        $lockedCoupon->increment('used_count');
    }

    public function getFormattedCoupon(Coupon $coupon): array
    {
        return [
            'id'             => $coupon->id,
            'code'           => $coupon->code,
            'discount_type'  => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
            'is_free_shipping'=> $coupon->is_free_shipping,
        ];
    }

    protected function validateApplicableItems(Coupon $coupon, array $cartItems): bool
    {
        $productIds = collect($cartItems)->pluck('product_id');

        if ($coupon->applicable_to === 'categories') {
            $categoryIds = $coupon->categories()->pluck('categories.id');
            return Product::whereIn('id', $productIds)
                ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
                ->exists();
        }

        if ($coupon->applicable_to === 'products') {
            $validIds = $coupon->products()->pluck('products.id');
            return $productIds->intersect($validIds)->isNotEmpty();
        }

        return false;
    }

    protected function getApplicableSubtotal(Coupon $coupon, array $cartItems): array
    {
        $productIds = collect($cartItems)->pluck('product_id');

        if ($coupon->applicable_to === 'categories') {
            $categoryIds = $coupon->categories()->pluck('categories.id');
            $validIds = Product::whereIn('id', $productIds)
                ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
                ->pluck('id');
        } elseif ($coupon->applicable_to === 'products') {
            $validIds = $coupon->products()->pluck('products.id');
        } else {
            $validIds = $productIds;
        }

        // Load DB prices instead of trusting client-supplied prices
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $variantIds = collect($cartItems)->pluck('variant_id')->filter();
        $variants = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');

        $subtotal = 0;
        foreach ($cartItems as $item) {
            if (!$validIds->contains($item['product_id'])) {
                continue;
            }

            $price = 0;
            if (!empty($item['variant_id']) && isset($variants[$item['variant_id']])) {
                $v = $variants[$item['variant_id']];
                $price = $v->sale_price ?? $v->regular_price ?? 0;
            } elseif (isset($products[$item['product_id']])) {
                $p = $products[$item['product_id']];
                $price = $p->sale_price ?? $p->regular_price ?? 0;
            }

            $subtotal += $price * ($item['quantity'] ?? 1);
        }

        return [
            'subtotal' => $subtotal,
            'product_ids' => $validIds->toArray(),
        ];
    }
}