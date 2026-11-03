<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LoyaltyTransaction;
use App\Models\PointLot;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExpireLoyaltyLotsCommand extends Command
{
    protected $signature = 'loyalty:expire-lots';
    protected $description = 'Expire outdated loyalty point lots and update customer spendable balances';

    public function handle(LoyaltyService $loyaltyService): int
    {
        $this->info('Scanning for expired loyalty point lots...');

        $expiredLots = PointLot::where('is_expired', false)
            ->where('remaining_points', '>', 0)
            ->where('expires_at', '<', now())
            ->with('account')
            ->get();

        $count = 0;
        foreach ($expiredLots as $lot) {
            $account = $lot->account;
            $lostPoints = $lot->remaining_points;

            $lot->update([
                'is_expired' => true,
                'remaining_points' => 0,
            ]);

            $newBalance = max(0, $account->current_points_balance - $lostPoints);
            $account->update(['current_points_balance' => $newBalance]);

            LoyaltyTransaction::create([
                'loyalty_account_id' => $account->id,
                'points_change' => -$lostPoints,
                'resulting_balance' => $newBalance,
                'resulting_debt' => $account->points_debt,
                'event_type' => 'expiry',
                'event_key' => 'expiry_' . $lot->id . '_' . Str::random(8),
                'notes' => "Expired {$lostPoints} points from batch #{$lot->id}",
            ]);

            $count++;
        }

        $this->info("Successfully expired {$count} loyalty point lots.");
        return self::SUCCESS;
    }
}
