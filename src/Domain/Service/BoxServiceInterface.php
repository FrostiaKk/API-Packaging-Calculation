<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Model\Box;

interface BoxServiceInterface
{
    /** @return Box[] */
    public function listAll(): array;

    public function getById(int $id): ?Box;

    public function create(float $width, float $height, float $length, float $maxWeight): Box;

    public function update(int $id, float $width, float $height, float $length, float $maxWeight): ?Box;

    public function delete(int $id): bool;
}
