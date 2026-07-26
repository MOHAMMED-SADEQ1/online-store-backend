<?php

namespace App\Services\Payment\Gateways;

use App\Models\PaymentMethod;
use App\Services\Payment\PaymentGateway;

class CodGateway implements PaymentGateway
{
    public function createPayment(array $source, PaymentMethod $method, array $options = []): array
    {
        return [
            'payment_id'       => 'COD-' . ($source['id'] ?? time()),
            'status'           => 'paid',
            'payment_url'      => null,
            'gateway_response' => [
                'method' => 'cod',
                'note'   => 'Cash on delivery - payment collected upon delivery',
            ],
        ];
    }

    public function verify(string $transactionId): array
    {
        return [
            'status'  => 'paid',
            'message' => 'COD payment is always considered paid on delivery.',
        ];
    }

    public function handleWebhook(array $payload): void
    {
        // COD has no webhooks
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        return [
            'status'  => 'refunded',
            'message' => 'COD refund processed manually.',
        ];
    }
}
