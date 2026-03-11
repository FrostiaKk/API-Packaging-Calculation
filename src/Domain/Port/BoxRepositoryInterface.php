<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Model\Box;

interface BoxRepositoryInterface
{
    /** @return Box[] */
    public function findAll(): array;

    public function findById(int $id): ?Box;

    public function save(Box $box): Box;

    public function delete(int $id): bool;
}
