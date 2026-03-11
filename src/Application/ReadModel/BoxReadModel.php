<?php

declare(strict_types=1);

namespace App\Application\ReadModel;

use App\Domain\Model\Box;

final readonly class BoxReadModel
{
    private function __construct(
        public ?int $id,
        public string $externalId,
        public float $width,
        public float $height,
        public float $length,
        public float $maxWeight,
    ) {
    }

    public static function fromDomainModel(Box $box): self
    {
        return new self(
            id: $box->id,
            externalId: $box->externalId,
            width: $box->width,
            height: $box->height,
            length: $box->length,
            maxWeight: $box->maxWeight,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->externalId,
            'width' => $this->width,
            'height' => $this->height,
            'length' => $this->length,
            'maxWeight' => $this->maxWeight,
        ];
    }
}
