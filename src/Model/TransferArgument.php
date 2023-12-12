<?php

namespace App\Model;

use App\Entity\Account;

class TransferArgument
{
    public function __construct(
        private Account $payer,
        private Account $payee,
        private int $amount,
        private bool $tolerateStaleRates = false
    )
    {
        
    }

    public function getPayer(): Account
    {
        return $this->payer;
    }

    public function getPayee(): Account
    {
        return $this->payee;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getTolerateStaleRates(): bool
    {
        return $this->tolerateStaleRates;
    }

    public function setPayer(Account $payer): static
    {
        $this->payer = $payer;

        return $this;
    }

    public function setPayee(Account $payee): static
    {
        $this->payee = $payee;

        return $this;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function setTolerateStaleRates(bool $tolerateStaleRates): static
    {
        $this->tolerateStaleRates = $tolerateStaleRates;

        return $this;
    }
}