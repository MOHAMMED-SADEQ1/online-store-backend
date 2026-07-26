<?php

namespace App\Services\Payment;

use App\Models\PaymentMethod;
use App\Services\Payment\Gateways\CodGateway;
use App\Services\Payment\Gateways\MoyasarGateway;

class PaymentService
{
    private array $gateways = [];

    public function __construct()
    {
        $this->gateways = [
            'moyasar' => app(MoyasarGateway::class),
            'cod'     => app(CodGateway::class),
        ];
    }

    public function gateway(?string $name = null): PaymentGateway
    {
        $name = $name ?? 'moyasar';

        if (!isset($this->gateways[$name])) {
            throw new \InvalidArgumentException("Payment gateway '{$name}' is not registered.");
        }

        return $this->gateways[$name];
    }

    public function registerGateway(string $name, PaymentGateway $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    /**
     * @param array $source ['id', 'amount', 'description', 'metadata' => [...]]
     */
    public function initiatePayment(array $source, PaymentMethod $method, array $options = []): array
    {
        $gateway = $this->gateway($method->gateway);

        $result = $gateway->createPayment($source, $method, $options);

        return [
            'payment_id'  => $result['payment_id'],
            'payment_url' => $result['payment_url'] ?? null,
            'status'      => $result['status'],
            'gateway_response' => $result['gateway_response'] ?? [],
        ];
    }

    public function verifyPayment(string $transactionId, ?string $gateway = null): array
    {
        $gateway = $gateway ?? 'moyasar';
        return $this->gateway($gateway)->verify($transactionId);
    }

    public function handleWebhook(string $gateway, array $payload): void
    {
        $this->gateway($gateway)->handleWebhook($payload);
    }

    public function refund(string $transactionId, ?float $amount = null, ?string $gateway = null): array
    {
        $gateway = $gateway ?? 'moyasar';
        return $this->gateway($gateway)->refund($transactionId, $amount);
    }
}
