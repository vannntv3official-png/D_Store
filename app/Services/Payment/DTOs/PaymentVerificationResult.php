<?php

namespace App\Services\Payment\DTOs;

class PaymentVerificationResult
{
    public function __construct(
        public readonly bool $isPaid,
        public readonly string $gateway,
        public readonly ?string $transactionId = null,
        public readonly ?string $amount = null,
        public readonly ?string $currency = null,
        public readonly string $status = 'pending',
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = []
    ) {}
}
