<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\ExchangeRatesService;
use App\Repository\MetadataRepository;
use App\Repository\CurrencyExchangeRateRepository;
use Doctrine\ORM\EntityManager;
use App\Entity\Metadata;
use App\Service\TimeService;

final class ExchangeRatesServiceTest extends TestCase
{
    public function testAreRatesOutdatedIfMetadataIsNull()
    {
        $mockEntityManager = $this->createMock(EntityManager::class);
        $mockMetadataRepository = $this->createMock(MetadataRepository::class);
        $mockExchangeRateRepository = $this->createMock(CurrencyExchangeRateRepository::class);
        $mockTimeService = $this->createMock(TimeService::class);

        $exchangeRatesService = new ExchangeRatesService(
            $mockEntityManager,
            $mockExchangeRateRepository,
            $mockMetadataRepository,
            $mockTimeService
        );

        $mockMetadataRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        
        $this->assertTrue($exchangeRatesService->areRatesOutdated());
    }

    public function testAreRatesOutdatedIfDateIsOld()
    {
        $mockEntityManager = $this->createMock(EntityManager::class);
        $mockMetadataRepository = $this->createMock(MetadataRepository::class);
        $mockExchangeRateRepository = $this->createMock(CurrencyExchangeRateRepository::class);
        $mockTimeService = $this->createMock(TimeService::class);
        $exchangeRatesService = new ExchangeRatesService(
            $mockEntityManager,
            $mockExchangeRateRepository,
            $mockMetadataRepository,
            $mockTimeService
        );

        $metadata = new Metadata();
        $metadata->setIdentifier('currency_rates_last_update_time');
        $metadata->setValue('2021-01-01 00:00:00');

        $mockTimeService->expects($this->once())
            ->method('getCurrentDateTime')
            ->willReturn(new DateTime('2023-01-01 01:00:00'));
        $mockTimeService->expects($this->once())
            ->method('createFromFormat')
            ->willReturn(new DateTime('2021-01-01 00:00:00'));

        $mockMetadataRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($metadata);
        
        $this->assertTrue($exchangeRatesService->areRatesOutdated());
    }

    public function testAreRatesOutdatedIfDateIsNew()
    {
        $mockEntityManager = $this->createMock(EntityManager::class);
        $mockMetadataRepository = $this->createMock(MetadataRepository::class);
        $mockExchangeRateRepository = $this->createMock(CurrencyExchangeRateRepository::class);
        $mockTimeService = $this->createMock(TimeService::class);
        $exchangeRatesService = new ExchangeRatesService(
            $mockEntityManager,
            $mockExchangeRateRepository,
            $mockMetadataRepository,
            $mockTimeService
        );

        $metadata = new Metadata();
        $metadata->setIdentifier('currency_rates_last_update_time');
        $metadata->setValue('2021-01-01 00:00:00');

        $mockMetadataRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($metadata);
        
        $mockTimeService->expects($this->once())
            ->method('createFromFormat')
            ->willReturn(new DateTime('2021-01-01 00:00:00'));
        $mockTimeService->expects($this->once())
            ->method('getCurrentDateTime')
            ->willReturn(new DateTime('2021-01-01 01:00:00'));
        
        $this->assertTrue($exchangeRatesService->areRatesOutdated());
    }
}