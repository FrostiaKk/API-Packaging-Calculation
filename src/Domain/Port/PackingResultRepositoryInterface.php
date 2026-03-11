<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Model\CachedPackingResult;

interface PackingResultRepositoryInterface
{
    public function findByInputHash(string $inputHash): ?CachedPackingResult;

    public function save(string $inputHash, string $requestPayload, float $totalWeight, ?int $boxId): void;

    public function purgeAll(): void;
}
