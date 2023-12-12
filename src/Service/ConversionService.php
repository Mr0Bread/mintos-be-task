<?php

namespace App\Service;

use App\Repository\CurrencyExchangeRateRepository;
use App\Entity\CurrencyExchangeRate;

class ConversionService
{
    public function __construct(
        private CurrencyExchangeRateRepository $currencyExchangeRateRepository
    ) {
    }

    /**
     * Returns amount in base currency converted from target currency
     * So if you want to know hom much 100 EUR is in USD, you call this method
     * with parameters ('EUR', 'USD', 100)
     *
     * @param string $from - base currency
     * @param string $to - target currency
     * @param integer $targetAmount - amount in target currency
     * @return integer
     */
    public function convertToTarget(
        string $from,
        string $to,
        int $targetAmount,
        bool $tolerateStaleRates = false
    ): int {
        if ($from === $to) {
            return $targetAmount;
        }

        $exactExchangeRate = $this->currencyExchangeRateRepository->findOneBy([
            'base' => $from,
            'target' => $to
        ]);

        if ($exactExchangeRate) {
            return $this->convertWithExactExchangeRate(
                $from,
                $to,
                $targetAmount,
                $exactExchangeRate
            );
        }

        return $this->convertWithIndirectExchangeRate(
            $from,
            $to,
            $targetAmount
        );
    }

    private function convertWithIndirectExchangeRate(
        string $from,
        string $to,
        int $targetAmount
    ): int {
        $usdToTargetExchangeRate = $this->currencyExchangeRateRepository->findOneBy([
            'base' => 'USD',
            'target' => $to
        ]);

        $usdToPayerExchangeRate = $this->currencyExchangeRateRepository->findOneBy([
            'base' => 'USD',
            'target' => $from
        ]);

        if (!$usdToTargetExchangeRate || !$usdToPayerExchangeRate) {
            throw new \Exception('No exchange rate found');
        }

        $usdToTargetRate = $usdToTargetExchangeRate->getRate();
        $usdToPayerRate = $usdToPayerExchangeRate->getRate();

        $targetAmountAsUsd = round($targetAmount / $usdToTargetRate);
        return round($targetAmountAsUsd * $usdToPayerRate);
    }

    private function convertWithExactExchangeRate(string $from, string $to, int $targetAmount, CurrencyExchangeRate $exactExchangeRate): int
    {
        $exchangeRate = $exactExchangeRate->getRate();

        return round($targetAmount * $exchangeRate);
    }
}
