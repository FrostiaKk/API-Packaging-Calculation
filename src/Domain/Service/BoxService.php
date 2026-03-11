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

    public function create(Box $box): Box
    {
        $created = $this->boxRepository->save($box);

        $this->packingResultRepository->purgeAll();

        return $created;
    }

    public function update(int $id, Box $box): ?Box
    {
        $existing = $this->boxRepository->findById($id);
        if ($existing === null) {
            return null;
        }

        $saved = $this->boxRepository->save($box);

        $this->packingResultRepository->purgeAll();

        return $saved;
    }

    public function delete(int $id): bool
    {
        return $this->boxRepository->delete($id);
    }
}
