<?php

declare(strict_types=1);

namespace App\Domain\Model;

final readonly class CachedPackingResult
{
    public function __construct(
        public ?Box $box,
    ) {
    }
}
