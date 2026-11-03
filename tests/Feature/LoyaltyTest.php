<?php

namespace Tests\Feature;

use App\Models\LoyaltyAccount;
use App\Models\Order;
use App\Models\Reward;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Pricing\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LoyaltyTest extends TestCase
{
    use DatabaseTransactions;

    protected LoyaltyService $loyaltyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loyaltyService = app(LoyaltyService::class);
    }

    public function test_registration_points_awarded_once(): void
    {
        $user = User::factory()->create();

        $tx = $this->loyaltyService->awardRegistrationPoints($user);
        $this->assertNotNull($tx);
        $this->assertEquals(50, $tx->points_change);
        $this->assertEquals(50, $user->loyaltyAccount->fresh()->current_points_balance);

        // Attempt duplicate award
        $duplicateTx = $this->loyaltyService->awardRegistrationPoints($user);
        $this->assertNull($duplicateTx);
        $this->assertEquals(50, $user->loyaltyAccount->fresh()->current_points_balance);
    }

    public function test_order_awards_first_order_bonus_and_spend_points(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'order_number' => 'LOYALTY-TEST-001',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '+85512333444',
            'subtotal' => 200.00,
            'discount_total' => 0.00,
            'tax_total' => 0.00,
            'shipping_total' => 0.00,
            'grand_total' => 200.00,
            'settlement_amount' => 820000.00,
            'fulfillment_status' => 'delivered',
            'payment_status' => 'paid',
        ]);

        $txs = $this->loyaltyService->awardOrderPoints($order);

        // Expect 2 transactions: First order (100) + Spend points (200) = 300 points
        $this->assertCount(2, $txs);
        $account = $user->loyaltyAccount->fresh();
        $this->assertEquals(300, $account->current_points_balance);
        $this->assertEquals(300, $account->lifetime_points_earned);
    }

    public function test_refund_after_redemption_records_points_debt_and_never_negative_balance(): void
    {
        $user = User::factory()->create();

        // 1. Order 1 completes: awards 100 first-order bonus + 50 spend points = 150 points
        $order1 = Order::create([
            'order_number' => 'REFUND-LOYALTY-001',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '+85512555666',
            'subtotal' => 50.00,
            'grand_total' => 50.00,
            'settlement_amount' => 205000.00,
            'fulfillment_status' => 'delivered',
            'payment_status' => 'paid',
        ]);
        $this->loyaltyService->awardOrderPoints($order1);

        $account = $user->loyaltyAccount->fresh();
        $this->assertEquals(150, $account->current_points_balance);

        // 2. Customer redeems 100 points for a $5 voucher (balance becomes 50)
        $reward = Reward::first();
        $this->loyaltyService->redeemReward($user, $reward);
        $this->assertEquals(50, $account->fresh()->current_points_balance);

        // 3. Customer also redeems remaining 50 points (balance becomes 0)
        $account->current_points_balance = 0;
        $account->save();

        // 4. Order 1 is refunded! Customer earned 50 spend points on order 1, but has 0 balance.
        // Clawback 50 points
        $tx = $this->loyaltyService->clawbackRefundPoints($order1, Money::of('50.00'));
        $this->assertNotNull($tx);

        $refreshedAccount = $account->fresh();
        // Crucial invariant: Balance is clamped at 0, deficit stored in points_debt
        $this->assertEquals(0, $refreshedAccount->current_points_balance);
        $this->assertEquals(50, $refreshedAccount->points_debt);
        $this->assertEquals(0, $refreshedAccount->spendable_points);

        // 5. Customer earns 70 points on order 2: 50 automatically clears debt, remaining 20 is spendable
        $order2 = Order::create([
            'order_number' => 'REFUND-LOYALTY-002',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '+85512555666',
            'subtotal' => 70.00,
            'grand_total' => 70.00,
            'settlement_amount' => 287000.00,
            'fulfillment_status' => 'delivered',
            'payment_status' => 'paid',
        ]);

        $this->loyaltyService->awardOrderPoints($order2);

        $finalAccount = $account->fresh();
        $this->assertEquals(0, $finalAccount->points_debt); // Debt cleared!
        $this->assertEquals(20, $finalAccount->current_points_balance); // 70 - 50 = 20
        $this->assertEquals(20, $finalAccount->spendable_points);
    }
}
