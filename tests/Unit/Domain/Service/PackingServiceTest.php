<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Exception\CalculationException;
use App\Domain\Exception\NotPackableException;
use App\Domain\Model\Box;
use App\Domain\Model\CachedPackingResult;
use App\Domain\Model\PackingInput;
use App\Domain\Model\Product;
use App\Domain\Port\BoxRepositoryInterface;
use App\Domain\Port\PackingCalculatorInterface;
use App\Domain\Model\PackingSource;
use App\Domain\Port\PackingResultRepositoryInterface;
use App\Domain\Service\PackingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PackingServiceTest extends TestCase
{
    private PackingCalculatorInterface&MockObject $apiCalculator;
    private PackingCalculatorInterface&MockObject $fallbackCalculator;
    private PackingResultRepositoryInterface&MockObject $resultRepository;
    private BoxRepositoryInterface&MockObject $boxRepository;
    private PackingService $service;

    protected function setUp(): void
    {
        $this->apiCalculator = $this->createMock(PackingCalculatorInterface::class);
        $this->fallbackCalculator = $this->createMock(PackingCalculatorInterface::class);
        $this->resultRepository = $this->createMock(PackingResultRepositoryInterface::class);
        $this->boxRepository = $this->createMock(BoxRepositoryInterface::class);

        $this->service = new PackingService(
            $this->apiCalculator,
            $this->fallbackCalculator,
            $this->resultRepository,
            $this->boxRepository,
        );
    }

    private function createInput(): PackingInput
    {
        return new PackingInput([
            new Product(3.0, 2.0, 1.0, 5.0),
        ]);
    }

    private function createBox(): Box
    {
        return new Box(1, 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d', 5.0, 5.0, 5.0, 20.0);
    }

    public function testReturnsCachedResultOnCacheHit(): void
    {
        $cached = new CachedPackingResult(box: $this->createBox());

        $this->resultRepository
            ->method('findByInputHash')
            ->willReturn($cached);

        $this->apiCalculator->expects(self::never())->method('calculate');
        $this->fallbackCalculator->expects(self::never())->method('calculate');

        $result = $this->service->pack($this->createInput());

        self::assertSame(PackingSource::Cache, $result->source);
        self::assertNotNull($result->box);
    }

    public function testCallsApiOnCacheMiss(): void
    {
        $this->resultRepository->method('findByInputHash')->willReturn(null);
        $this->boxRepository->method('findAll')->willReturn([$this->createBox()]);

        $this->apiCalculator->method('calculate')->willReturn($this->createBox());

        $this->resultRepository->expects(self::once())->method('save');

        $result = $this->service->pack($this->createInput());

        self::assertSame(PackingSource::Api, $result->source);
        self::assertNotNull($result->box);
    }

    public function testFallsBackWhenApiThrows(): void
    {
        $this->resultRepository->method('findByInputHash')->willReturn(null);
        $this->boxRepository->method('findAll')->willReturn([$this->createBox()]);

        $this->apiCalculator->method('calculate')
            ->willThrowException(new CalculationException('API down'));

        $this->fallbackCalculator->method('calculate')->willReturn($this->createBox());

        // Fallback results should NOT be cached
        $this->resultRepository->expects(self::never())->method('save');

        $result = $this->service->pack($this->createInput());

        self::assertSame(PackingSource::Fallback, $result->source);
        self::assertNotNull($result->box);
    }

    public function testThrowsNotPackableWhenApiReturnsNull(): void
    {
        $this->resultRepository->method('findByInputHash')->willReturn(null);
        $this->boxRepository->method('findAll')->willReturn([$this->createBox()]);
        $this->apiCalculator->method('calculate')->willReturn(null);

        $this->expectException(NotPackableException::class);

        $this->service->pack($this->createInput());
    }

    public function testThrowsNotPackableWhenNoBoxesConfigured(): void
    {
        $this->resultRepository->method('findByInputHash')->willReturn(null);
        $this->boxRepository->method('findAll')->willReturn([]);

        $this->expectException(NotPackableException::class);
        $this->expectExceptionMessage('No boxes configured');

        $this->service->pack($this->createInput());
    }

    public function testThrowsNotPackableWhenCachedResultHasNoBox(): void
    {
        $cached = new CachedPackingResult(box: null);

        $this->resultRepository->method('findByInputHash')->willReturn($cached);

        $this->expectException(NotPackableException::class);

        $this->service->pack($this->createInput());
    }

    public function testCachesNotPackableApiResult(): void
    {
        $this->resultRepository->method('findByInputHash')->willReturn(null);
        $this->boxRepository->method('findAll')->willReturn([$this->createBox()]);
        $this->apiCalculator->method('calculate')->willReturn(null);

        // Should cache the null (not-packable) result
        $this->resultRepository->expects(self::once())->method('save');

        try {
            $this->service->pack($this->createInput());
        } catch (NotPackableException) {
            // Expected
        }
    }

    public function testCachesAndRethrowsNotPackableExceptionFromApi(): void
    {
        $this->resultRepository->method('findByInputHash')->willReturn(null);
        $this->boxRepository->method('findAll')->willReturn([$this->createBox()]);

        $this->apiCalculator->method('calculate')
            ->willThrowException(new NotPackableException('One or more items cannot fit into any available box.'));

        // Should cache the not-packable result before re-throwing
        $this->resultRepository->expects(self::once())->method('save');

        // Fallback should NOT be invoked
        $this->fallbackCalculator->expects(self::never())->method('calculate');

        try {
            $this->service->pack($this->createInput());
            self::fail('Expected NotPackableException');
        } catch (NotPackableException $e) {
            self::assertSame('One or more items cannot fit into any available box.', $e->getMessage());
        }
    }
}
