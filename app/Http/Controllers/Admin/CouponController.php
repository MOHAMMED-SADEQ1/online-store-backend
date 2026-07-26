<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(protected CouponService $couponService) {}

    public function index(Request $request): JsonResponse
    {
        $coupons = Coupon::withCount('usage')
            ->when($request->search, fn($q, $v) => $q->where('code', 'like', "%{$v}%"))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active))
            ->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($coupons);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'                 => 'required|string|max:255|unique:coupons,code',
            'discount_type'        => 'required|in:percentage,fixed',
            'discount_value'       => 'required|numeric|min:0',
            'minimum_order_amount' => 'numeric|min:0',
            'maximum_discount'     => 'nullable|numeric|min:0',
            'applicable_to'        => 'sometimes|in:all,categories,products',
            'minimum_quantity'     => 'nullable|integer|min:1',
            'exclude_sale_items'   => 'boolean',
            'usage_limit'          => 'integer|min:0',
            'start_date'           => 'nullable|date',
            'end_date'             => 'nullable|date|after_or_equal:start_date',
            'is_active'            => 'boolean',
            'categories'           => 'array|exists:categories,id',
            'products'             => 'array|exists:products,id',
            'is_free_shipping'     => 'boolean',
            'per_user_limit'       => 'nullable|integer|min:1',
            'user_id'              => 'nullable|integer|exists:users,id',
            'min_orders_count'     => 'nullable|integer|min:1',
        ]);

        $coupon = Coupon::create($data);

        if (isset($data['categories'])) {
            $coupon->categories()->sync($data['categories']);
        }
        if (isset($data['products'])) {
            $coupon->products()->sync($data['products']);
        }

        return response()->json([
            'message' => 'Coupon created successfully.',
            'coupon'  => $this->couponService->getFormattedCoupon($coupon),
        ], 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $coupon->load(['categories', 'products', 'usage.user', 'usage.order']);

        return response()->json(['coupon' => $coupon]);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $data = $request->validate([
            'code'                 => 'sometimes|string|max:255|unique:coupons,code,' . $coupon->id,
            'discount_type'        => 'sometimes|in:percentage,fixed',
            'discount_value'       => 'sometimes|numeric|min:0',
            'minimum_order_amount' => 'numeric|min:0',
            'maximum_discount'     => 'nullable|numeric|min:0',
            'applicable_to'        => 'sometimes|in:all,categories,products',
            'minimum_quantity'     => 'nullable|integer|min:1',
            'exclude_sale_items'   => 'boolean',
            'usage_limit'          => 'integer|min:0',
            'start_date'           => 'nullable|date',
            'end_date'             => 'nullable|date|after_or_equal:start_date',
            'is_active'            => 'boolean',
            'categories'           => 'array|exists:categories,id',
            'products'             => 'array|exists:products,id',
            'is_free_shipping'     => 'boolean',
            'per_user_limit'       => 'nullable|integer|min:1',
            'user_id'              => 'nullable|integer|exists:users,id',
            'min_orders_count'     => 'nullable|integer|min:1',
        ]);

        $coupon->update($data);

        if (isset($data['categories'])) {
            $coupon->categories()->sync($data['categories']);
        }
        if (isset($data['products'])) {
            $coupon->products()->sync($data['products']);
        }

        return response()->json([
            'message' => 'Coupon updated successfully.',
            'coupon'  => $this->couponService->getFormattedCoupon($coupon),
        ]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted successfully.']);
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'       => 'required|string|max:255|exists:coupons,code',
            'subtotal'   => 'nullable|numeric|min:0',
            'cart_items' => 'nullable|array',
        ]);

        $coupon = Coupon::where('code', $data['code'])->firstOrFail();

        $result = $this->couponService->validateCoupon(
            $coupon,
            $request->user()?->id,
            $data['cart_items'] ?? null,
            $data['subtotal'] ?? null,
        );

        if (!$result['valid']) {
            return response()->json(['valid' => false, 'message' => $result['message']], 422);
        }

        $discount = $this->couponService->calculateDiscount($coupon, $data['subtotal'] ?? 0, $data['cart_items'] ?? null);

        return response()->json([
            'valid'    => true,
            'coupon'   => $coupon,
            'discount' => $discount,
        ]);
    }
}
