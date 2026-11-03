<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use App\Services\Order\OrderStateMachineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KhqrWebhookController extends Controller
{
    public function __invoke(Request $request, OrderStateMachineService $orderStateMachine): JsonResponse
    {
        $payload = $request->all();
        $billNumber = $payload['bill_number'] ?? $payload['billNumber'] ?? null;
        $externalTxnId = $payload['transaction_id'] ?? $payload['hash'] ?? $payload['external_id'] ?? null;
        $status = $payload['status'] ?? 'SUCCESS';

        if (!$billNumber) {
            return response()->json(['error' => 'Missing bill_number'], 400);
        }

        $eventId = 'khqr_' . ($externalTxnId ?: $billNumber . '_' . now()->timestamp);

        // Deduplicate webhook event
        if (PaymentWebhookEvent::where('gateway', 'khqr_sandbox')->where('event_id', $eventId)->exists()) {
            return response()->json(['message' => 'Event already processed'], 200);
        }

        $payment = Payment::where('merchant_reference', $billNumber)
            ->orWhereHas('order', fn ($q) => $q->where('order_number', $billNumber))
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment record not found'], 404);
        }

        DB::transaction(function () use ($payment, $eventId, $payload, $status, $externalTxnId, $orderStateMachine) {
            PaymentWebhookEvent::create([
                'gateway' => 'khqr_sandbox',
                'event_id' => $eventId,
                'payload' => $payload,
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            if (strtoupper((string) $status) === 'SUCCESS') {
                $payment->update([
                    'status' => 'paid',
                ]);

                PaymentTransaction::create([
                    'payment_id' => $payment->id,
                    'type' => 'charge',
                    'amount' => $payment->amount,
                    'status' => 'success',
                    'gateway_transaction_id' => $externalTxnId,
                    'gateway_response' => $payload,
                ]);

                $order = $payment->order;
                $orderStateMachine->transitionPayment(
                    order: $order,
                    toStatus: 'paid',
                    actor: null,
                    reason: "Payment captured via Bakong KHQR (Ref: {$externalTxnId})"
                );
            }
        });

        return response()->json(['status' => 'success'], 200);
    }
}
