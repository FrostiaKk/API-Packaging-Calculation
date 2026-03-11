<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Model\Box;
use App\Domain\Port\BoxRepositoryInterface;
use App\Domain\Port\PackingResultRepositoryInterface;

final class BoxService implements BoxServiceInterface
{
    public function __construct(
        private readonly BoxRepositoryInterface $boxRepository,
        private readonly PackingResultRepositoryInterface $packingResultRepository,
    ) {
    }

    /** @return Box[] */
    public function listAll(): array
    {
        return $this->boxRepository->findAll();
    }

    public function getById(int $id): ?Box
    {
        return $this->boxRepository->findById($id);
    }

    public function create(float $width, float $height, float $length, float $maxWeight): Box
    {
        $box = new Box(null, $width, $height, $length, $maxWeight);
        $created = $this->boxRepository->save($box);

        $this->packingResultRepository->purgeAll();

        return $created;
    }

    public function update(int $id, float $width, float $height, float $length, float $maxWeight): ?Box
    {
        $existing = $this->boxRepository->findById($id);
        if ($existing === null) {
            return null;
        }

        $updated = new Box($id, $width, $height, $length, $maxWeight);
        $saved = $this->boxRepository->save($updated);

        $this->packingResultRepository->purgeAll();

        return $saved;
    }

    public function delete(int $id): bool
    {
        return $this->boxRepository->delete($id);
    }
}
