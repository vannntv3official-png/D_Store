<?php

namespace App\Services\Payment\DTOs;

class RefundResult
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly ?string $refundTransactionId = null,
        public readonly string $status = 'pending', // 'succeeded', 'pending', 'failed'
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = []
    ) {}
}
