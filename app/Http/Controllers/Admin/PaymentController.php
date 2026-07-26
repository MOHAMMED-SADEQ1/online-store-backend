<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::with('order:id,order_number', 'method')
            ->when($request->order_id, fn($q, $v) => $q->where('order_id', $v))
            ->when($request->payment_status, fn($q, $v) => $q->where('payment_status', $v))
            ->when($request->payment_method, fn($q, $v) => $q->where('payment_method', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($payments);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id'        => 'required|exists:orders,id',
            'method_id'       => 'nullable|exists:payment_methods,id',
            'payment_method'  => 'nullable|string|max:100',
            'transaction_id'  => 'nullable|string|max:255',
            'amount'          => 'required|numeric|min:0',
            'payment_status'  => 'sometimes|in:pending,completed,failed,refunded',
            'payment_date'    => 'nullable|date',
            'gateway_response' => 'nullable|json',
        ]);

        if (isset($data['gateway_response'])) {
            $data['gateway_response'] = json_decode($data['gateway_response'], true);
        }

        $payment = Payment::create($data);

        return response()->json([
            'message' => 'Payment recorded.',
            'payment' => $payment->load('order', 'method'),
        ], 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load('order', 'method');

        return response()->json(['payment' => $payment]);
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $data = $request->validate([
            'payment_status'  => 'sometimes|in:pending,completed,failed,refunded',
            'transaction_id'  => 'nullable|string|max:255',
            'gateway_response' => 'nullable|json',
        ]);

        if (isset($data['gateway_response'])) {
            $data['gateway_response'] = json_decode($data['gateway_response'], true);
        }

        $payment->update($data);

        return response()->json([
            'message' => 'Payment updated.',
            'payment' => $payment->fresh()->load('order', 'method'),
        ]);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return response()->json(['message' => 'Payment deleted.']);
    }
}
