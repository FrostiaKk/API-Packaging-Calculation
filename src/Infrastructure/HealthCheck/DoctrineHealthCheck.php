<?php

declare(strict_types=1);

namespace App\Infrastructure\HealthCheck;

use App\Domain\Port\HealthCheckInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function isDatabaseHealthy(): bool
    {
        try {
            $this->entityManager->getConnection()->executeQuery('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
