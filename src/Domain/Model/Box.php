<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Exception\DomainInvariantException;

final readonly class Box
{
    public function __construct(
        public ?int $id,
        public string $externalId,
        public float $width,
        public float $height,
        public float $length,
        public float $maxWeight,
    ) {
        if (trim($this->externalId) === '') {
            throw new DomainInvariantException('External ID must not be empty.');
        }

        if ($this->width <= 0 || $this->height <= 0 || $this->length <= 0) {
            throw new DomainInvariantException('Box dimensions must be positive.');
        }

        if ($this->maxWeight <= 0) {
            throw new DomainInvariantException('Max weight must be positive.');
        }
    }

    public function volume(): float
    {
        return $this->width * $this->height * $this->length;
    }

    /** @return float[] Dimensions sorted ascending for rotation normalization. */
    public function normalizedDimensions(): array
    {
        $dims = [$this->width, $this->height, $this->length];
        sort($dims);
        return $dims;
    }
}
