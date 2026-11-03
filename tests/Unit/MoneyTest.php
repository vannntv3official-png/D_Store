<?php

namespace Tests\Unit;

use App\Services\Pricing\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_can_create_and_format_usd(): void
    {
        $money = Money::of('1299.50', 'USD');
        $this->assertEquals('1299.5000', $money->getAmount());
        $this->assertEquals('$1,299.50', $money->format());
        $this->assertEquals(129950, $money->toMinorUnits());
    }

    public function test_can_add_and_subtract_monies(): void
    {
        $a = Money::of('100.25', 'USD');
        $b = Money::of('50.50', 'USD');

        $sum = $a->add($b);
        $diff = $a->subtract($b);

        $this->assertEquals('150.7500', $sum->getAmount());
        $this->assertEquals('49.7500', $diff->getAmount());
    }

    public function test_pro_rata_allocation_has_zero_penny_discrepancy(): void
    {
        // Allocate $10.00 discount across 3 items priced $20, $30, $50
        $discount = Money::of('10.00', 'USD');
        $allocated = $discount->allocate([20, 30, 50]);

        $this->assertCount(3, $allocated);
        $this->assertEquals('2.0000', $allocated[0]->getAmount());
        $this->assertEquals('3.0000', $allocated[1]->getAmount());
        $this->assertEquals('5.0000', $allocated[2]->getAmount());

        // Allocate odd discount $10.00 across 3 equal items ($3.33, $3.33, $3.34)
        $allocatedOdd = $discount->allocate([1, 1, 1]);
        $sumAllocated = $allocatedOdd[0]->add($allocatedOdd[1])->add($allocatedOdd[2]);
        $this->assertEquals('10.0000', $sumAllocated->getAmount());
    }

    public function test_khr_conversion_rounds_to_nearest_100(): void
    {
        // $10.25 * 4100 = 42,025 KHR -> rounds to nearest 100 => 42,000 KHR
        $usd = Money::of('10.25', 'USD');
        $khr = $usd->toKhr(4100.0);

        $this->assertEquals('KHR', $khr->getCurrency());
        $this->assertEquals('42000.0000', $khr->getAmount());
        $this->assertEquals('៛42,000', $khr->format());
    }
}
