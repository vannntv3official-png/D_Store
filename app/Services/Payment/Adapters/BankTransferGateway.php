<?php

namespace App\Services\Payment\Adapters;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\PaymentInitiationResult;
use App\Services\Payment\DTOs\PaymentVerificationResult;
use App\Services\Payment\DTOs\PaymentWebhookResult;
use App\Services\Payment\DTOs\RefundResult;
use App\Services\Pricing\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'bank_transfer';
    }

    public function initiatePayment(Order $order, array $options = []): PaymentInitiationResult
    {
        $payment = Payment::firstOrCreate(
            ['order_id' => $order->id, 'gateway' => 'bank_transfer'],
            [
                'currency' => $order->base_currency,
                'amount' => $order->grand_total,
                'status' => 'pending',
                'merchant_reference' => 'BT-' . $order->order_number . '-' . Str::random(4),
            ]
        );

        $instructions = Setting::get('bank_transfer_instructions', [
            'bank_name' => 'ABA Bank',
            'account_name' => 'D STORE CO., LTD',
            'account_number_usd' => '001 888 777',
            'account_number_khr' => '001 888 778',
            'note' => 'Please include your Order Number as payment reference.',
        ]);

        return new PaymentInitiationResult(
            isSuccess: true,
            gateway: 'bank_transfer',
            instructions: json_encode($instructions)
        );
    }

    public function verifyPayment(Payment $payment): PaymentVerificationResult
    {
        return new PaymentVerificationResult(
            isPaid: $payment->status === 'paid',
            gateway: 'bank_transfer',
            status: $payment->status
        );
    }

    public function handleWebhook(Request $request): PaymentWebhookResult
    {
        return new PaymentWebhookResult(isValid: false, action: 'ignore', errorMessage: 'Bank transfer uses manual staff reconciliation.');
    }

    public function refund(PaymentTransaction $transaction, Money $amount, string $reason): RefundResult
    {
        return new RefundResult(
            isSuccess: true,
            refundTransactionId: 'MANUAL-BT-REFUND-' . Str::random(8),
            status: 'succeeded'
        );
    }

    public function supportsRefunds(): bool
    {
        return true;
    }
}
