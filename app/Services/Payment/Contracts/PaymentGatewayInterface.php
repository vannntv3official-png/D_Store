<?php

namespace App\Services\Payment\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\Payment\DTOs\PaymentInitiationResult;
use App\Services\Payment\DTOs\PaymentVerificationResult;
use App\Services\Payment\DTOs\PaymentWebhookResult;
use App\Services\Payment\DTOs\RefundResult;
use App\Services\Pricing\Money;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function getIdentifier(): string;

    public function initiatePayment(Order $order, array $options = []): PaymentInitiationResult;

    public function verifyPayment(Payment $payment): PaymentVerificationResult;

    public function handleWebhook(Request $request): PaymentWebhookResult;

    public function refund(PaymentTransaction $transaction, Money $amount, string $reason): RefundResult;

    public function supportsRefunds(): bool;
}
