<?php

namespace App\Service;

use App\Repository\TransactionRepository;
use App\Entity\Account;
use App\Model\TransferArgument;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Transaction;
use App\Service\ConversionService;
use App\Repository\CurrencyExchangeRateRepository;
use App\Entity\CurrencyExchangeRate;

class TransferService
{
    public function __construct(
        private ExchangeRatesService $exchangeRatesService,
        private EntityManagerInterface $entityManager,
        private ConversionService $conversionService,
        private CurrencyExchangeRateRepository $currencyExchangeRateRepository
    )
    {
        
    }

    public function transfer(TransferArgument $transferArgument)
    {
        $tolerateStaleRates = $transferArgument->getTolerateStaleRates();

        try {
            if ($this->exchangeRatesService->areRatesOutdated()) {
                $this->exchangeRatesService->updateRates();
            }
        } catch (\Exception $e) {
            if (!$tolerateStaleRates) {
                throw new \Exception('Rates are outdated and cannot be updated at the moment');
            }
        }

        $payer = $transferArgument->getPayer();
        $payee = $transferArgument->getPayee();
        $targetAmount = $transferArgument->getAmount();
        $transferAmount = $this->conversionService->convertToTarget(
            $payer->getCurrency(),
            $payee->getCurrency(),
            $targetAmount,
        );

        $transaction = new Transaction();

        $transaction->setPayer($payer);
        $transaction->setPayee($payee);
        $transaction->setAmount($transferAmount);

        $this->entityManager->persist($transaction);

        $payer->setBalance($payer->getBalance() - $transferAmount);
        $payee->setBalance($payee->getBalance() + $targetAmount);

        $this->entityManager->flush();

        return [
            'payer' => $payer,
            'payee' => $payee,
            'amount' => $transferAmount
        ];
    }
}