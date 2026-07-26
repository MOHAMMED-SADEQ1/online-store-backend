<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(protected CouponService $couponService) {}

    public function validateCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'       => 'required|string|max:50',
            'subtotal'   => 'required|numeric|min:0',
            'cart_items' => 'nullable|array',
            'cart_items.*.product_id' => 'required_with:cart_items|integer|exists:products,id',
            'cart_items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'cart_items.*.quantity'   => 'required_with:cart_items|integer|min:1',
        ]);

        $userId = $request->user()?->id;

        $result = $this->couponService->validate(
            $data['code'],
            $data['subtotal'],
            $userId,
            $data['cart_items'] ?? null,
        );

        if (!$result['valid']) {
            return response()->json([
                'valid'  => false,
                'message'=> $result['message'],
            ], 422);
        }

        return response()->json([
            'valid'            => true,
            'coupon'           => $result['coupon'],
            'discount_amount'  => $result['discount_amount'],
            'is_free_shipping' => $result['is_free_shipping'] ?? false,
        ]);
    }
}
