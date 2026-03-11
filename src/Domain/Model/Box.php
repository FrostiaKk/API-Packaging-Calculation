<?php

declare(strict_types=1);

namespace App\Domain\Model;

final readonly class Box
{
    public function __construct(
        public ?int $id,
        public float $width,
        public float $height,
        public float $length,
        public float $maxWeight,
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'width' => $this->width,
            'height' => $this->height,
            'length' => $this->length,
            'maxWeight' => $this->maxWeight,
        ];
    }
}
