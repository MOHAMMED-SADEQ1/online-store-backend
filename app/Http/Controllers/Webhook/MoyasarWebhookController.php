<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MoyasarWebhookController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    public function handle(Request $request): JsonResponse
    {
        Log::info('Moyasar webhook received', $request->all());

        try {
            $this->paymentService->handleWebhook('moyasar', $request->all());

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Moyasar webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
