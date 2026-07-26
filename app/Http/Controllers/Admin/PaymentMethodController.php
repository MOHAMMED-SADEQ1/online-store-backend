<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'payment_methods' => PaymentMethod::orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar'       => 'required|string|max:50',
            'name_en'       => 'required|string|max:50',
            'is_active'     => 'boolean',
            'additional_fee' => 'numeric|min:0',
        ]);

        $method = PaymentMethod::create($data);

        return response()->json([
            'message'        => 'Payment method created successfully.',
            'payment_method' => $method,
        ], 201);
    }

    public function update(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $data = $request->validate([
            'name_ar'       => 'sometimes|string|max:50',
            'name_en'       => 'sometimes|string|max:50',
            'is_active'     => 'boolean',
            'additional_fee' => 'numeric|min:0',
        ]);

        $paymentMethod->update($data);

        return response()->json([
            'message'        => 'Payment method updated successfully.',
            'payment_method' => $paymentMethod->fresh(),
        ]);
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->delete();

        return response()->json(['message' => 'Payment method deleted successfully.']);
    }
}
