<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Model\Box;
use App\Domain\Port\BoxRepositoryInterface;
use App\Infrastructure\Persistence\Entity\Packaging;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineBoxRepository implements BoxRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @return Box[] */
    public function findAll(): array
    {
        $entities = $this->entityManager
            ->getRepository(Packaging::class)
            ->findAll();

        return array_map(fn(Packaging $e) => $e->toModel(), $entities);
    }

    public function findById(int $id): ?Box
    {
        $entity = $this->entityManager->find(Packaging::class, $id);
        return $entity?->toModel();
    }

    public function save(Box $box): Box
    {
        if ($box->id !== null) {
            $entity = $this->entityManager->find(Packaging::class, $box->id);
            if ($entity === null) {
                throw new \RuntimeException("Box with ID {$box->id} not found.");
            }
            $entity->setExternalId($box->externalId);
            $entity->setWidth($box->width);
            $entity->setHeight($box->height);
            $entity->setLength($box->length);
            $entity->setMaxWeight($box->maxWeight);
        } else {
            $entity = new Packaging($box->externalId, $box->width, $box->height, $box->length, $box->maxWeight);
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();

        return $entity->toModel();
    }

    public function delete(int $id): bool
    {
        $entity = $this->entityManager->find(Packaging::class, $id);
        if ($entity === null) {
            return false;
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        return true;
    }
}
