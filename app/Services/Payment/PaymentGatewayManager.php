<?php

namespace App\Services\Payment;

use App\Services\Payment\Adapters\AbaPaywaySandboxGateway;
use App\Services\Payment\Adapters\BankTransferGateway;
use App\Services\Payment\Adapters\CodGateway;
use App\Services\Payment\Adapters\KhqrSandboxGateway;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGatewayInterface> */
    protected array $drivers = [];

    public function __construct()
    {
        $this->register(new CodGateway());
        $this->register(new BankTransferGateway());
        $this->register(new KhqrSandboxGateway());
        $this->register(new AbaPaywaySandboxGateway());
    }

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->drivers[$gateway->getIdentifier()] = $gateway;
    }

    public function driver(string $name): PaymentGatewayInterface
    {
        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not supported.");
        }

        return $this->drivers[$name];
    }

    public function getAvailableGateways(): array
    {
        return array_keys($this->drivers);
    }
}
