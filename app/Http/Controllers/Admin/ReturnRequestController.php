<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnRequestController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'return_requests' => ReturnRequest::with([
                'user:id,username,email,first_name,last_name',
                'order:id,order_number,final_amount',
                'items.product:id,name_ar,name_en',
                'items.orderItem:id,unit_price,quantity',
            ])
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }

    public function show(ReturnRequest $returnRequest): JsonResponse
    {
        return response()->json([
            'return_request' => $returnRequest->load([
                'user',
                'order',
                'items.product',
                'items.orderItem',
                'exchangeOrder',
            ]),
        ]);
    }

    public function updateStatus(Request $request, ReturnRequest $returnRequest): JsonResponse
    {
        $data = $request->validate([
            'status'        => 'required|in:pending,approved,rejected,items_received,refunded,completed',
            'refund_amount' => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string',
        ]);

        $returnRequest->update($data);

        return response()->json([
            'message'        => 'Return request status updated successfully.',
            'return_request' => $returnRequest->fresh()->load([
                'user:id,username,email,first_name,last_name',
                'order:id,order_number',
                'items.product:id,name_ar,name_en',
            ]),
        ]);
    }

    public function destroy(ReturnRequest $returnRequest): JsonResponse
    {
        $returnRequest->items()->delete();
        $returnRequest->delete();

        return response()->json(['message' => 'Return request deleted successfully.']);
    }
}
