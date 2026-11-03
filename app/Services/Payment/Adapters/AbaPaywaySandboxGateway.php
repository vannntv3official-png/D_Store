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

class AbaPaywaySandboxGateway implements PaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'aba_payway_sandbox';
    }

    public function initiatePayment(Order $order, array $options = []): PaymentInitiationResult
    {
        $payment = Payment::where('order_id', $order->id)
            ->where('gateway', 'aba_payway_sandbox')
            ->first();

        if (!$payment) {
            $merchantRef = 'ABAPW-' . $order->order_number . '-' . Str::upper(Str::random(4));
            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway' => 'aba_payway_sandbox',
                'currency' => $order->base_currency,
                'amount' => $order->grand_total,
                'status' => 'pending',
                'merchant_reference' => $merchantRef,
            ]);
        } else {
            $merchantRef = $payment->merchant_reference;
        }

        $reqTime = date('YmdHis');
        $merchantId = config('services.aba_payway.merchant_id', 'ec432100');
        $apiKey = config('services.aba_payway.api_key', 'sandbox_aba_payway_key');
        $amount = number_format((float) $order->grand_total, 2, '.', '');

        // Generate PayWay HMAC-SHA512 Signature
        $hashString = $reqTime . $merchantId . $merchantRef . $amount;
        $hash = base64_encode(hash_hmac('sha512', $hashString, $apiKey, true));

        $sandboxCheckoutUrl = route('payway.sandbox.checkout', ['reference' => $merchantRef]);
        $deeplink = "abamobilebank://payway?tran_id={$merchantRef}";
        $qrString = "https://payway.ababank.com/pay/{$merchantRef}?amount={$amount}&currency={$order->base_currency}";
        $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrString);

        return new PaymentInitiationResult(
            isSuccess: true,
            gateway: 'aba_payway_sandbox',
            paymentUrl: $sandboxCheckoutUrl,
            qrCodePayload: $qrString,
            qrImageUrl: $qrImageUrl,
            deeplink: $deeplink,
            instructions: "Pay securely with ABA Mobile, Visa, Mastercard, or UnionPay via ABA PayWay.",
            rawResponse: [
                'merchant_id' => $merchantId,
                'tran_id' => $merchantRef,
                'req_time' => $reqTime,
                'hash' => $hash,
            ]
        );
    }

    public function verifyPayment(Payment $payment): PaymentVerificationResult
    {
        return new PaymentVerificationResult(
            isPaid: $payment->status === 'paid',
            gateway: 'aba_payway_sandbox',
            transactionId: $payment->merchant_reference,
            amount: (string) $payment->amount,
            currency: $payment->currency,
            status: $payment->status
        );
    }

    public function handleWebhook(Request $request): PaymentWebhookResult
    {
        $payload = $request->all();
        $apiKey = config('services.aba_payway.api_key', 'sandbox_aba_payway_key');

        $tranId = $payload['tran_id'] ?? null;
        $status = $payload['status'] ?? null;
        $hash = $payload['hash'] ?? null;

        // In sandbox, simulate validation
        $action = ($status === '0' || $status === 'PAID' || $status === 'SUCCESS') ? 'mark_paid' : 'ignore';

        return new PaymentWebhookResult(
            isValid: true,
            eventId: 'PAYWAY-EVT-' . ($payload['apv'] ?? Str::random(10)),
            orderNumber: $payload['order_number'] ?? null,
            gatewayTransactionId: $tranId,
            amount: (string) ($payload['amount'] ?? '0'),
            currency: $payload['currency'] ?? 'USD',
            action: $action,
            payload: $payload
        );
    }

    public function refund(PaymentTransaction $transaction, Money $amount, string $reason): RefundResult
    {
        return new RefundResult(
            isSuccess: true,
            refundTransactionId: 'PAYWAY-REFUND-' . Str::random(10),
            status: 'succeeded'
        );
    }

    public function supportsRefunds(): bool
    {
        return true;
    }
}
