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

class AbaPaywayWebhookController extends Controller
{
    public function __invoke(Request $request, OrderStateMachineService $orderStateMachine): JsonResponse
    {
        $tranId = $request->input('tran_id');
        $status = (int) $request->input('status', 0); // 0 = Approved / Success in ABA PayWay
        $apv = $request->input('apv');

        if (!$tranId) {
            return response()->json(['error' => 'Missing tran_id'], 400);
        }

        $eventId = 'payway_' . $tranId . '_' . ($apv ?? now()->timestamp);

        // Deduplicate webhook event
        if (PaymentWebhookEvent::where('gateway', 'aba_payway_sandbox')->where('event_id', $eventId)->exists()) {
            return response()->json(['message' => 'Event already processed'], 200);
        }

        $payment = Payment::where('merchant_reference', $tranId)
            ->orWhereHas('order', fn ($q) => $q->where('order_number', $tranId))
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment record not found'], 404);
        }

        DB::transaction(function () use ($payment, $eventId, $request, $status, $apv, $orderStateMachine) {
            PaymentWebhookEvent::create([
                'gateway' => 'aba_payway_sandbox',
                'event_id' => $eventId,
                'payload' => $request->all(),
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            if ($status === 0) { // Success
                $payment->update([
                    'status' => 'paid',
                ]);

                PaymentTransaction::create([
                    'payment_id' => $payment->id,
                    'type' => 'charge',
                    'amount' => $payment->amount,
                    'status' => 'success',
                    'gateway_transaction_id' => $apv,
                    'gateway_response' => $request->all(),
                ]);

                $order = $payment->order;
                $orderStateMachine->transitionPayment(
                    order: $order,
                    toStatus: 'paid',
                    actor: null,
                    reason: "Payment captured via ABA PayWay (APV: {$apv})"
                );
            }
        });

        return response()->json(['status' => '00', 'description' => 'Success'], 200);
    }
}
