<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use InvalidArgumentException;

class Money
{
    private string $amount; // Formatted with 4 decimal places for precision
    private string $currency; // USD, KHR

    public function __construct(string|float|int $amount, string $currency = 'USD')
    {
        $this->currency = strtoupper($currency);
        $this->amount = number_format((float) $amount, 4, '.', '');
    }

    public static function of(string|float|int $amount, string $currency = 'USD'): self
    {
        return new self($amount, $currency);
    }

    public static function zero(string $currency = 'USD'): self
    {
        return new self('0.0000', $currency);
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function toDecimal(): float
    {
        return (float) $this->amount;
    }

    public function toMinorUnits(): int
    {
        if ($this->currency === 'KHR') {
            return (int) round((float) $this->amount);
        }
        return (int) round((float) $this->amount * 100);
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);
        return new self(bcadd($this->amount, $other->amount, 4), $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);
        $result = bcsub($this->amount, $other->amount, 4);
        return new self($result, $this->currency);
    }

    public function multiply(string|float|int $multiplier): self
    {
        $multStr = number_format((float) $multiplier, 4, '.', '');
        return new self(bcmul($this->amount, $multStr, 4), $this->currency);
    }

    public function divide(string|float|int $divisor, int $scale = 4): self
    {
        if ((float) $divisor == 0.0) {
            throw new InvalidArgumentException('Division by zero.');
        }
        $divStr = number_format((float) $divisor, 4, '.', '');
        return new self(bcdiv($this->amount, $divStr, $scale), $this->currency);
    }

    public function isGreaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);
        return bccomp($this->amount, $other->amount, 4) === 1;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->ensureSameCurrency($other);
        return bccomp($this->amount, $other->amount, 4) >= 0;
    }

    public function isLessThan(self $other): bool
    {
        $this->ensureSameCurrency($other);
        return bccomp($this->amount, $other->amount, 4) === -1;
    }

    public function isLessThanOrEqual(self $other): bool
    {
        $this->ensureSameCurrency($other);
        return bccomp($this->amount, $other->amount, 4) <= 0;
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0.0000', 4) === 0;
    }

    public function isPositive(): bool
    {
        return bccomp($this->amount, '0.0000', 4) === 1;
    }

    public function isNegative(): bool
    {
        return bccomp($this->amount, '0.0000', 4) === -1;
    }

    /**
     * Allocate a total amount across line items proportionally with exact penny reconciliation.
     * Guaranteed that sum(allocated) === original total.
     *
     * @param int[]|float[] $ratios
     * @return self[]
     */
    public function allocate(array $ratios): array
    {
        $totalRatio = array_sum($ratios);
        if ($totalRatio <= 0) {
            return array_fill(0, count($ratios), self::zero($this->currency));
        }

        $totalCents = $this->toMinorUnits();
        $remainder = $totalCents;
        $results = [];

        foreach ($ratios as $ratio) {
            $share = (int) floor(($totalCents * $ratio) / $totalRatio);
            $results[] = $share;
            $remainder -= $share;
        }

        // Distribute remainder pennies to the largest shares
        for ($i = 0; $i < $remainder; $i++) {
            $results[$i % count($results)]++;
        }

        return array_map(function ($cents) {
            $amount = $this->currency === 'KHR' ? (string) $cents : number_format($cents / 100, 4, '.', '');
            return new self($amount, $this->currency);
        }, $results);
    }

    public function formatMoney(bool $withSymbol = true): string
    {
        if ($this->currency === 'KHR') {
            $rounded = (int) round((float) $this->amount);
            $formatted = number_format($rounded);
            return $withSymbol ? '៛' . $formatted : $formatted;
        }

        $formatted = number_format((float) $this->amount, 2, '.', ',');
        return $withSymbol ? '$' . $formatted : $formatted;
    }

    /**
     * Convert USD to KHR at specified exchange rate, rounded to nearest 100 KHR.
     */
    public function toKhrMoney(float $exchangeRate = 4100.0): self
    {
        if ($this->currency === 'KHR') {
            return $this;
        }

        $khrAmount = (float) $this->amount * $exchangeRate;
        // Round to nearest 100 Riels (Cambodia monetary convention)
        $roundedKhr = round($khrAmount / 100) * 100;
        return new self($roundedKhr, 'KHR');
    }

    public function __call(string $name, array $arguments): mixed
    {
        if ($name === 'format') {
            return $this->formatMoney($arguments[0] ?? true);
        }
        if ($name === 'toKhr') {
            return $this->toKhrMoney((float) ($arguments[0] ?? 4100.0));
        }
        throw new \BadMethodCallException("Method [{$name}] does not exist on " . static::class);
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        if ($name === 'format') {
            $val = $arguments[0] ?? 0;
            if ($val instanceof self) {
                return $val->formatMoney($arguments[1] ?? false);
            }
            $formatted = number_format((float) $val, 2, '.', ',');
            return ($arguments[1] ?? false) ? '$' . $formatted : $formatted;
        }

        if ($name === 'toKhr') {
            $val = $arguments[0] ?? 0;
            $rate = (float) ($arguments[1] ?? 4100.0);
            if ($val instanceof self) {
                return $val->toKhrMoney($rate);
            }
            $khrAmount = (float) $val * $rate;
            return (int) (round($khrAmount / 100) * 100);
        }

        if ($name === 'add') {
            $a = $arguments[0] instanceof self ? $arguments[0]->getAmount() : number_format((float) $arguments[0], 4, '.', '');
            $b = $arguments[1] instanceof self ? $arguments[1]->getAmount() : number_format((float) $arguments[1], 4, '.', '');
            return bcadd($a, $b, 4);
        }

        if ($name === 'multiply') {
            $a = $arguments[0] instanceof self ? $arguments[0]->getAmount() : number_format((float) $arguments[0], 4, '.', '');
            $b = number_format((float) $arguments[1], 4, '.', '');
            return bcmul($a, $b, 4);
        }

        throw new \BadMethodCallException("Static method [{$name}] does not exist on " . static::class);
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}");
        }
    }
}
