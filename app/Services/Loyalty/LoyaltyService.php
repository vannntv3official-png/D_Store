<?php

namespace App\Services\Loyalty;

use App\Models\Coupon;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\PointLot;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Setting;
use App\Models\User;
use App\Services\Pricing\Money;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsufficientPointsException extends Exception {}

class LoyaltyService
{
    public function getOrCreateAccount(User $user): LoyaltyAccount
    {
        return LoyaltyAccount::firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * Award registration bonus points upon verified account registration.
     */
    public function awardRegistrationPoints(User $user): ?LoyaltyTransaction
    {
        $points = (int) Setting::get('loyalty_registration_points', 50);
        if ($points <= 0) {
            return null;
        }

        $eventKey = "REGISTRATION_USER_{$user->id}";

        return DB::transaction(function () use ($user, $points, $eventKey) {
            $account = LoyaltyAccount::firstOrCreate(['user_id' => $user->id]);
            /** @var LoyaltyAccount $lockedAccount */
            $lockedAccount = LoyaltyAccount::where('id', $account->id)->lockForUpdate()->first();

            if (LoyaltyTransaction::where('event_key', $eventKey)->exists()) {
                return null; // Already awarded
            }

            return $this->applyPointsCredit(
                account: $lockedAccount,
                points: $points,
                eventType: 'registration',
                eventKey: $eventKey,
                notes: 'Welcome bonus for account registration'
            );
        });
    }

    /**
     * Award points for a qualifying completed order (delivered AND paid).
     */
    public function awardOrderPoints(Order $order): array
    {
        if (!$order->user_id) {
            return []; // Guest orders do not have an attached loyalty account
        }

        $user = $order->user;
        $transactions = [];

        DB::transaction(function () use ($order, $user, &$transactions) {
            $account = LoyaltyAccount::firstOrCreate(['user_id' => $user->id]);
            /** @var LoyaltyAccount $lockedAccount */
            $lockedAccount = LoyaltyAccount::where('id', $account->id)->lockForUpdate()->first();

            // 1. Check & award First Order Bonus atomically
            $firstOrderEventKey = "FIRST_ORDER_USER_{$user->id}";
            $firstOrderBonus = (int) Setting::get('loyalty_first_order_points', 100);

            if ($firstOrderBonus > 0 && !LoyaltyTransaction::where('event_key', $firstOrderEventKey)->exists()) {
                $txFirst = $this->applyPointsCredit(
                    account: $lockedAccount,
                    points: $firstOrderBonus,
                    eventType: 'first_order',
                    eventKey: $firstOrderEventKey,
                    orderId: $order->id,
                    notes: "First completed order bonus for order #{$order->order_number}"
                );
                if ($txFirst) {
                    $transactions[] = $txFirst;
                }
            }

            // 2. Spend Points: 1 point per eligible USD spent on merchandise (excluding tax and shipping)
            $spendEventKey = "ORDER_SPEND_{$order->id}";
            if (!LoyaltyTransaction::where('event_key', $spendEventKey)->exists()) {
                // Eligible merchandise spend = subtotal - discount_total
                $eligibleSpend = max(0.0, (float) $order->subtotal - (float) $order->discount_total);
                $ratio = (float) Setting::get('loyalty_spend_point_ratio', 1.0);
                $spendPoints = (int) floor($eligibleSpend * $ratio);

                if ($spendPoints > 0) {
                    $txSpend = $this->applyPointsCredit(
                        account: $lockedAccount,
                        points: $spendPoints,
                        eventType: 'order_spend',
                        eventKey: $spendEventKey,
                        orderId: $order->id,
                        notes: "Earned {$spendPoints} points for order #{$order->order_number}"
                    );
                    if ($txSpend) {
                        $transactions[] = $txSpend;
                    }
                }
            }
        });

        return $transactions;
    }

    /**
     * Claw back points on product return or refund.
     * Available points can NEVER become negative. Any deficit is placed into points_debt.
     */
    public function clawbackRefundPoints(Order $order, Money $refundedMerchandiseAmount): ?LoyaltyTransaction
    {
        if (!$order->user_id) {
            return null;
        }

        $user = $order->user;
        $ratio = (float) Setting::get('loyalty_spend_point_ratio', 1.0);
        $pointsToClawback = (int) floor($refundedMerchandiseAmount->toDecimal() * $ratio);

        if ($pointsToClawback <= 0) {
            return null;
        }

        $eventKey = "REFUND_CLAWBACK_ORDER_{$order->id}_" . Str::random(8);

        return DB::transaction(function () use ($user, $pointsToClawback, $eventKey, $order) {
            $account = LoyaltyAccount::firstOrCreate(['user_id' => $user->id]);
            /** @var LoyaltyAccount $lockedAccount */
            $lockedAccount = LoyaltyAccount::where('id', $account->id)->lockForUpdate()->first();

            $currentBalance = $lockedAccount->current_points_balance;

            if ($currentBalance >= $pointsToClawback) {
                // Sufficient balance: deduct directly
                $lockedAccount->current_points_balance -= $pointsToClawback;
                $pointsDeducted = $pointsToClawback;
            } else {
                // Insufficient balance: drain balance to zero and record remainder as debt
                $pointsDeducted = $currentBalance;
                $debtIncrease = $pointsToClawback - $currentBalance;
                $lockedAccount->current_points_balance = 0;
                $lockedAccount->points_debt += $debtIncrease;
            }

            $lockedAccount->save();

            $signature = $this->generateSignature($lockedAccount->id, -$pointsToClawback, $lockedAccount->current_points_balance, $eventKey);

            return LoyaltyTransaction::create([
                'loyalty_account_id' => $lockedAccount->id,
                'points_change' => -$pointsToClawback,
                'resulting_balance' => $lockedAccount->current_points_balance,
                'resulting_debt' => $lockedAccount->points_debt,
                'event_type' => 'refund_clawback',
                'order_id' => $order->id,
                'event_key' => $eventKey,
                'signature' => $signature,
                'notes' => "Clawed back {$pointsToClawback} points for refunded items on order #{$order->order_number}",
            ]);
        });
    }

    /**
     * Redeem points for a reward voucher.
     */
    public function redeemReward(User $user, Reward $reward): RewardRedemption
    {
        return DB::transaction(function () use ($user, $reward) {
            $account = LoyaltyAccount::firstOrCreate(['user_id' => $user->id]);
            /** @var LoyaltyAccount $lockedAccount */
            $lockedAccount = LoyaltyAccount::where('id', $account->id)->lockForUpdate()->first();

            if ($lockedAccount->points_debt > 0) {
                throw new InsufficientPointsException("Cannot redeem rewards while an unsettled points debt of {$lockedAccount->points_debt} exists.");
            }

            if ($lockedAccount->current_points_balance < $reward->points_cost) {
                throw new InsufficientPointsException("Insufficient points. Required: {$reward->points_cost}, Available: {$lockedAccount->current_points_balance}");
            }

            // Deduct points from balance
            $lockedAccount->current_points_balance -= $reward->points_cost;
            $lockedAccount->lifetime_points_spent += $reward->points_cost;
            $lockedAccount->save();

            // Deduct from point lots FIFO
            $this->deductFromPointLots($lockedAccount, $reward->points_cost);

            // Create corresponding coupon for customer checkout
            $couponCode = 'REW-' . strtoupper(Str::random(8));
            $coupon = Coupon::create([
                'code' => $couponCode,
                'name' => [
                    'en' => "Loyalty Reward: {$reward->getLocalizedName('en')}",
                    'km' => "រង្វាន់ពិន្ទុ៖ {$reward->getLocalizedName('km')}",
                ],
                'type' => $reward->discount_type,
                'value' => $reward->discount_value,
                'min_spend_amount' => $reward->min_spend_amount,
                'usage_limit_total' => 1,
                'usage_limit_per_user' => 1,
                'is_active' => true,
                'expires_at' => now()->addDays(30),
            ]);

            $eventKey = "REDEMPTION_REW_{$reward->id}_" . Str::random(8);
            $signature = $this->generateSignature($lockedAccount->id, -$reward->points_cost, $lockedAccount->current_points_balance, $eventKey);

            LoyaltyTransaction::create([
                'loyalty_account_id' => $lockedAccount->id,
                'points_change' => -$reward->points_cost,
                'resulting_balance' => $lockedAccount->current_points_balance,
                'resulting_debt' => $lockedAccount->points_debt,
                'event_type' => 'redemption',
                'event_key' => $eventKey,
                'signature' => $signature,
                'notes' => "Redeemed {$reward->points_cost} points for reward: {$reward->getLocalizedName('en')}",
            ]);

            return RewardRedemption::create([
                'loyalty_account_id' => $lockedAccount->id,
                'reward_id' => $reward->id,
                'points_spent' => $reward->points_cost,
                'coupon_id' => $coupon->id,
                'status' => 'completed',
            ]);
        });
    }

    private function applyPointsCredit(
        LoyaltyAccount $account,
        int $points,
        string $eventType,
        string $eventKey,
        ?int $orderId = null,
        ?string $notes = null
    ): LoyaltyTransaction {
        $creditRemaining = $points;

        // If debt exists, credit first pays down debt
        if ($account->points_debt > 0) {
            if ($account->points_debt >= $creditRemaining) {
                $account->points_debt -= $creditRemaining;
                $creditRemaining = 0;
            } else {
                $creditRemaining -= $account->points_debt;
                $account->points_debt = 0;
            }
        }

        $account->current_points_balance += $creditRemaining;
        $account->lifetime_points_earned += $points;
        $account->save();

        // Create Point Lot with expiration
        $expiryMonths = (int) Setting::get('loyalty_lot_expiry_months', 12);
        PointLot::create([
            'loyalty_account_id' => $account->id,
            'order_id' => $orderId,
            'initial_points' => $points,
            'remaining_points' => $points,
            'expires_at' => now()->addMonths($expiryMonths),
            'is_expired' => false,
        ]);

        $signature = $this->generateSignature($account->id, $points, $account->current_points_balance, $eventKey);

        return LoyaltyTransaction::create([
            'loyalty_account_id' => $account->id,
            'points_change' => $points,
            'resulting_balance' => $account->current_points_balance,
            'resulting_debt' => $account->points_debt,
            'event_type' => $eventType,
            'order_id' => $orderId,
            'event_key' => $eventKey,
            'signature' => $signature,
            'notes' => $notes,
        ]);
    }

    private function deductFromPointLots(LoyaltyAccount $account, int $pointsToDeduct): void
    {
        $remainingToDeduct = $pointsToDeduct;
        $lots = PointLot::where('loyalty_account_id', $account->id)
            ->where('is_expired', false)
            ->where('remaining_points', '>', 0)
            ->orderBy('expires_at', 'asc')
            ->get();

        foreach ($lots as $lot) {
            if ($lot->remaining_points >= $remainingToDeduct) {
                $lot->remaining_points -= $remainingToDeduct;
                $lot->save();
                break;
            } else {
                $remainingToDeduct -= $lot->remaining_points;
                $lot->remaining_points = 0;
                $lot->save();
            }
        }
    }

    private function generateSignature(int $accountId, int $pointsChange, int $resultingBalance, string $eventKey): string
    {
        $secret = config('app.key', 'dstore_fallback_secret');
        return hash_hmac('sha256', "{$accountId}|{$pointsChange}|{$resultingBalance}|{$eventKey}", $secret);
    }
}
