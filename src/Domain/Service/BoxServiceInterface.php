<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Model\Box;

interface BoxServiceInterface
{
    /** @return Box[] */
    public function listAll(): array;

    public function getById(int $id): ?Box;

    public function create(Box $box): Box;

    public function update(int $id, Box $box): ?Box;

    public function delete(int $id): bool;
}
