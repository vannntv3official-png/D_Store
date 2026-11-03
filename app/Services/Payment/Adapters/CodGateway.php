<?php

namespace App\Services\Payment\Adapters;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\PaymentInitiationResult;
use App\Services\Payment\DTOs\PaymentVerificationResult;
use App\Services\Payment\DTOs\PaymentWebhookResult;
use App\Services\Payment\DTOs\RefundResult;
use App\Services\Pricing\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CodGateway implements PaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'cod';
    }

    public function initiatePayment(Order $order, array $options = []): PaymentInitiationResult
    {
        $payment = Payment::firstOrCreate(
            ['order_id' => $order->id, 'gateway' => 'cod'],
            [
                'currency' => $order->base_currency,
                'amount' => $order->grand_total,
                'status' => 'pending',
                'merchant_reference' => 'COD-' . $order->order_number . '-' . Str::random(4),
            ]
        );

        return new PaymentInitiationResult(
            isSuccess: true,
            gateway: 'cod',
            instructions: 'Cash on Delivery. Please prepare cash upon courier delivery.'
        );
    }

    public function verifyPayment(Payment $payment): PaymentVerificationResult
    {
        return new PaymentVerificationResult(
            isPaid: $payment->status === 'paid',
            gateway: 'cod',
            status: $payment->status
        );
    }

    public function handleWebhook(Request $request): PaymentWebhookResult
    {
        return new PaymentWebhookResult(isValid: false, action: 'ignore', errorMessage: 'Webhooks not applicable for COD.');
    }

    public function refund(PaymentTransaction $transaction, Money $amount, string $reason): RefundResult
    {
        // COD refunds are audited cash disbursements recorded by staff
        return new RefundResult(
            isSuccess: true,
            refundTransactionId: 'MANUAL-COD-REFUND-' . Str::random(8),
            status: 'succeeded'
        );
    }

    public function supportsRefunds(): bool
    {
        return true;
    }
}
