<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Model\CachedPackingResult;
use App\Domain\Port\PackingResultRepositoryInterface;
use App\Infrastructure\Persistence\Entity\Packaging;
use App\Infrastructure\Persistence\Entity\PackingResult;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

final class DoctrinePackingResultRepository implements PackingResultRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findByInputHash(string $inputHash): ?CachedPackingResult
    {
        $entity = $this->entityManager
            ->getRepository(PackingResult::class)
            ->findOneBy(['inputHash' => $inputHash]);

        if ($entity === null) {
            return null;
        }

        return new CachedPackingResult(
            box: $entity->getBox()?->toModel(),
        );
    }

    public function save(string $inputHash, string $requestPayload, float $totalWeight, ?int $boxId): void
    {
        $existing = $this->entityManager
            ->getRepository(PackingResult::class)
            ->findOneBy(['inputHash' => $inputHash]);

        if ($existing !== null) {
            return;
        }

        $box = $boxId !== null
            ? $this->entityManager->find(Packaging::class, $boxId)
            : null;

        $result = new PackingResult(
            id: Uuid::uuid4(),
            inputHash: $inputHash,
            requestPayload: $requestPayload,
            totalWeight: $totalWeight,
            box: $box,
        );

        try {
            $this->entityManager->persist($result);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $this->entityManager->clear();
        }
    }

    public function purgeAll(): void
    {
        $this->entityManager
            ->createQuery('DELETE FROM ' . PackingResult::class)
            ->execute();
    }
}
