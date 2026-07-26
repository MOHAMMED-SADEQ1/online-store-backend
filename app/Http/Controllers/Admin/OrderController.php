<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getOrders($request->all());

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        $order = $this->orderService->getOrder($order);

        return response()->json(['order' => new OrderResource($order)]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'order_status'   => 'sometimes|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'payment_status' => 'sometimes|in:pending,paid,failed,refunded',
        ]);

        $order = $this->orderService->updateStatus($order, $data);

        return response()->json([
            'message' => 'Order status updated.',
            'order'   => new OrderResource($order),
        ]);
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->orderService->deleteOrder($order);

        return response()->json(['message' => 'Order deleted successfully.']);
    }
}
