<?php

declare(strict_types=1);

namespace App\Domain\Model;

final readonly class Product
{
    public function __construct(
        public float $width,
        public float $height,
        public float $length,
        public float $weight,
    ) {
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
