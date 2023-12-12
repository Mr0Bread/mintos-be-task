<?php

namespace App\Service;

use App\Entity\CurrencyExchangeRate;
use App\Entity\Metadata;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CurrencyExchangeRateRepository;
use App\Repository\MetadataRepository;
use App\Service\TimeService;

class ExchangeRatesService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CurrencyExchangeRateRepository $exchangeRateRepository,
        private MetadataRepository $metadataRepository,
        private TimeService $timeService
    ) {
    }

    public function areRatesOutdated()
    {
        date_default_timezone_set('UTC');

        $lastUpdateTimeMetadata = $this->metadataRepository->findOneBy([
            'identifier' => 'currency_rates_last_update_time'
        ]);

        if ($lastUpdateTimeMetadata) {
            $dateString = $lastUpdateTimeMetadata->getValue();
            $dateOfLastUpdate = $this->timeService->createFromFormat('Y-m-d H:i:s', $dateString);
            $currentDate = $this->timeService->getCurrentDateTime();
            $interval = $dateOfLastUpdate->diff($currentDate);

            return $interval->h >= 1;
        }

        return true;
    }

    public function updateRates()
    {
        $dbExchangeRates = $this->exchangeRateRepository->findAll();
        $dbExchangeRatesMap = [];

        foreach ($dbExchangeRates as $dbExchangeRate) {
            $dbExchangeRatesMap[$dbExchangeRate->getBase()][$dbExchangeRate->getTarget()] = $dbExchangeRate;
        }

        $ratesConfig = $this->fetchRatesConfig();

        $base = $ratesConfig['base'];
        $apiRates = $ratesConfig['rates'];

        foreach ($apiRates as $target => $rate) {
            if (isset($dbExchangeRatesMap[$base][$target])) {
                $dbExchangeRate = $dbExchangeRatesMap[$base][$target];
                $dbExchangeRate->setRate($rate);
            } else {
                $dbExchangeRate = new CurrencyExchangeRate();
                $dbExchangeRate->setBase($base);
                $dbExchangeRate->setTarget($target);
                $dbExchangeRate->setRate($rate);
                $this->entityManager->persist($dbExchangeRate);
            }
        }

        $lastUpdateTimeMetadata = $this->metadataRepository->findOneBy([
            'identifier' => 'currency_rates_last_update_time'
        ]);

        if (!$lastUpdateTimeMetadata) {
            $lastUpdateTimeMetadata = new Metadata();
            $lastUpdateTimeMetadata->setIdentifier('currency_rates_last_update_time');
            $this->entityManager->persist($lastUpdateTimeMetadata);
        }

        $lastUpdateTimeMetadata->setValue(($this->timeService->getCurrentDateTime())->format('Y-m-d H:i:s'));

        $this->entityManager->flush();
    }

    public function fetchRatesConfig(): array
    {
        $exchangeRateAppId = $_ENV['EXCHANGE_RATE_APP_ID'];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://openexchangerates.org/api/latest.json?app_id=$exchangeRateAppId",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "accept: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        } else {
            $jsonResponse = json_decode($response, true);

            if ($jsonResponse === null) {
                throw new \Exception('Failed to decode JSON response');
            }

            return $jsonResponse;
        }
    }
}
