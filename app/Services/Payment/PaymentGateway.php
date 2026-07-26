<?php

namespace App\Services\Payment;

use App\Models\PaymentMethod;

interface PaymentGateway
{
    /**
     * @param array $source ['amount' => float, 'id' => int|string, 'description' => string, 'metadata' => array]
     * @param PaymentMethod $method
     * @param array $options ['token' => string, 'callback_url' => string|null]
     * @return array ['payment_id', 'status', 'payment_url'?, 'gateway_response']
     */
    public function createPayment(array $source, PaymentMethod $method, array $options = []): array;

    public function verify(string $transactionId): array;

    public function handleWebhook(array $payload): void;

    public function refund(string $transactionId, ?float $amount = null): array;
}
