<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Exception\CalculationException;
use App\Domain\Exception\NotPackableException;
use App\Domain\Model\Box;
use App\Domain\Model\CachedPackingResult;
use App\Domain\Model\PackingInput;
use App\Domain\Model\PackingOutcome;
use App\Domain\Model\PackingSource;
use App\Domain\Port\BoxRepositoryInterface;
use App\Domain\Port\PackingCalculatorInterface;
use App\Domain\Port\PackingResultRepositoryInterface;

final class PackingService implements PackingServiceInterface
{
    public function __construct(
        private readonly PackingCalculatorInterface $apiCalculator,
        private readonly PackingCalculatorInterface $fallbackCalculator,
        private readonly PackingResultRepositoryInterface $packingResultRepository,
        private readonly BoxRepositoryInterface $boxRepository,
    ) {
    }

    public function pack(PackingInput $input): PackingOutcome
    {
        $inputHash = $input->computeInputHash();

        $cached = $this->packingResultRepository->findByInputHash($inputHash);
        if ($cached !== null) {
            return $this->buildOutcomeFromCache($cached);
        }

        $boxes = $this->boxRepository->findAll();
        if (empty($boxes)) {
            throw new NotPackableException('No boxes configured in the system.');
        }

        try {
            $resultBox = $this->apiCalculator->calculate($input, $boxes);
            $source = PackingSource::Api;
        } catch (NotPackableException $e) {
            $this->cacheResult($inputHash, $input, null);
            throw $e;
        } catch (CalculationException) {
            $resultBox = $this->fallbackCalculator->calculate($input, $boxes);
            $source = PackingSource::Fallback;
        }

        if ($source === PackingSource::Api) {
            $this->cacheResult($inputHash, $input, $resultBox);
        }

        if ($resultBox === null) {
            throw new NotPackableException('Products cannot be packed into a single available box.');
        }

        return new PackingOutcome(box: $resultBox, source: $source);
    }

    private function buildOutcomeFromCache(CachedPackingResult $cached): PackingOutcome
    {
        if ($cached->box === null) {
            throw new NotPackableException();
        }

        return new PackingOutcome(
            box: $cached->box,
            source: PackingSource::Cache,
        );
    }

    private function cacheResult(
        string $inputHash,
        PackingInput $input,
        ?Box $resultBox,
    ): void {
        $this->packingResultRepository->save(
            inputHash: $inputHash,
            requestPayload: json_encode([
                'products' => array_map(fn($p) => [
                    'width' => $p->width,
                    'height' => $p->height,
                    'length' => $p->length,
                    'weight' => $p->weight,
                ], $input->products),
            ], JSON_THROW_ON_ERROR),
            totalWeight: $input->totalWeight(),
            boxId: $resultBox?->id,
        );
    }
}
