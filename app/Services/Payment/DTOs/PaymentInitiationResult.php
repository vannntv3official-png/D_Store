<?php

namespace App\Services\Payment\DTOs;

class PaymentInitiationResult
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly string $gateway,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $qrCodePayload = null,
        public readonly ?string $qrImageUrl = null,
        public readonly ?string $deeplink = null,
        public readonly ?string $instructions = null,
        public readonly array $rawResponse = []
    ) {}

    public function toArray(): array
    {
        return [
            'is_success' => $this->isSuccess,
            'gateway' => $this->gateway,
            'payment_url' => $this->paymentUrl,
            'qr_code_payload' => $this->qrCodePayload,
            'qr_image_url' => $this->qrImageUrl,
            'deeplink' => $this->deeplink,
            'instructions' => $this->instructions,
        ];
    }
}
