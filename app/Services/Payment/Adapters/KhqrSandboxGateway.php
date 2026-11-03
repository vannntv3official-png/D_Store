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

class KhqrSandboxGateway implements PaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'khqr_sandbox';
    }

    public function initiatePayment(Order $order, array $options = []): PaymentInitiationResult
    {
        $merchantRef = 'KHQR-' . $order->order_number . '-' . Str::random(4);

        $payment = Payment::firstOrCreate(
            ['order_id' => $order->id, 'gateway' => 'khqr_sandbox'],
            [
                'currency' => $order->base_currency,
                'amount' => $order->grand_total,
                'status' => 'pending',
                'merchant_reference' => $merchantRef,
            ]
        );

        $currencyCode = $order->base_currency === 'KHR' ? '116' : '840';
        $amount = number_format((float) $order->grand_total, 2, '.', '');

        // Construct EMVCo Compliant KHQR Payload
        $khqrPayload = $this->buildEmvCoPayload(
            bakongAccountId: 'dstore@abaa',
            merchantName: 'D STORE PHNOM PENH',
            currencyCode: $currencyCode,
            amount: $amount,
            billNumber: $order->order_number
        );

        return new PaymentInitiationResult(
            isSuccess: true,
            gateway: 'khqr_sandbox',
            paymentUrl: route('khqr.sandbox.checkout', ['reference' => $merchantRef]),
            qrCodePayload: $khqrPayload,
            qrImageUrl: "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($khqrPayload),
            deeplink: "bakong://qr?payload=" . urlencode($khqrPayload),
            instructions: "Scan with any Bakong KHQR-supported banking app (ABA, Canadia, Wing, etc.).",
            rawResponse: ['merchant_reference' => $merchantRef, 'payload' => $khqrPayload]
        );
    }

    public function verifyPayment(Payment $payment): PaymentVerificationResult
    {
        // Sandbox verification checks payment status
        return new PaymentVerificationResult(
            isPaid: $payment->status === 'paid',
            gateway: 'khqr_sandbox',
            transactionId: $payment->merchant_reference,
            amount: (string) $payment->amount,
            currency: $payment->currency,
            status: $payment->status
        );
    }

    public function handleWebhook(Request $request): PaymentWebhookResult
    {
        $payload = $request->all();
        $signature = $request->header('X-KHQR-Signature');
        $secret = config('services.khqr.webhook_secret', 'sandbox_khqr_secret_key');

        $computedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($computedSignature, (string) $signature) && app()->isProduction()) {
            return new PaymentWebhookResult(
                isValid: false,
                errorMessage: 'Invalid KHQR webhook signature.'
            );
        }

        $orderNumber = $payload['order_number'] ?? null;
        $status = $payload['status'] ?? null;
        $eventId = $payload['event_id'] ?? 'KHQR-EVT-' . Str::random(12);

        $action = ($status === 'SUCCESS' || $status === 'PAID') ? 'mark_paid' : 'ignore';

        return new PaymentWebhookResult(
            isValid: true,
            eventId: $eventId,
            orderNumber: $orderNumber,
            gatewayTransactionId: $payload['transaction_id'] ?? null,
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
            refundTransactionId: 'KHQR-REFUND-' . Str::random(10),
            status: 'succeeded'
        );
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    private function buildEmvCoPayload(
        string $bakongAccountId,
        string $merchantName,
        string $currencyCode,
        string $amount,
        string $billNumber
    ): string {
        $data = [
            '00' => '01',
            '01' => '12', // Dynamic QR
            '29' => $this->formatTlv('00', $bakongAccountId),
            '52' => '5999', // General Merchandise
            '53' => $currencyCode,
            '54' => $amount,
            '58' => 'KH',
            '59' => substr($merchantName, 0, 25),
            '60' => 'PHNOM PENH',
            '62' => $this->formatTlv('01', $billNumber),
        ];

        $payloadWithoutCrc = '';
        foreach ($data as $tag => $val) {
            $payloadWithoutCrc .= $this->formatTlv($tag, $val);
        }
        $payloadWithoutCrc .= '6304';

        $crc = $this->calculateCrc16($payloadWithoutCrc);
        return $payloadWithoutCrc . $crc;
    }

    private function formatTlv(string $tag, string $value): string
    {
        $len = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);
        return $tag . $len . $value;
    }

    private function calculateCrc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
