<?php

namespace App\Services\Payment\DTOs;

class PaymentWebhookResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly ?string $eventId = null,
        public readonly ?string $orderNumber = null,
        public readonly ?string $gatewayTransactionId = null,
        public readonly ?string $amount = null,
        public readonly ?string $currency = null,
        public readonly string $action = 'ignore', // 'mark_paid', 'mark_failed', 'ignore'
        public readonly ?string $errorMessage = null,
        public readonly array $payload = []
    ) {}
}
