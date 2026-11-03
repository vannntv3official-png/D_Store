<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InventoryReservation;
use App\Models\Order;
use App\Services\Inventory\InventoryService;
use App\Services\Order\OrderStateMachineService;
use Illuminate\Console\Command;

class ExpireReservationsCommand extends Command
{
    protected $signature = 'orders:expire-reservations';
    protected $description = 'Release expired inventory reservations for unpaid checkout sessions';

    public function handle(InventoryService $inventoryService, OrderStateMachineService $orderStateMachine): int
    {
        $this->info('Checking for expired inventory reservations...');

        $expiredReservations = InventoryReservation::where('status', 'reserved')
            ->where('expires_at', '<', now())
            ->with(['variant.inventory', 'order'])
            ->get();

        $count = 0;
        foreach ($expiredReservations as $reservation) {
            $inventoryService->releaseReservation($reservation, 'reservation_expired');
            $count++;

            // If order was pending payment, cancel it
            if ($reservation->order && $reservation->order->fulfillment_status === Order::FULFILLMENT_PENDING) {
                try {
                    $orderStateMachine->transitionFulfillment(
                        order: $reservation->order,
                        toStatus: Order::FULFILLMENT_CANCELLED,
                        actor: null,
                        reason: 'Order automatically cancelled due to reservation expiration (unpaid checkout timeout).'
                    );
                } catch (\Throwable $e) {
                    $this->warn("Failed to cancel order {$reservation->order->order_number}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Successfully released {$count} expired inventory reservations.");
        return self::SUCCESS;
    }
}
