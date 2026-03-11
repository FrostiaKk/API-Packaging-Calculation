<?php

declare(strict_types=1);

namespace App\Domain\Model;

final readonly class PackingOutcome
{
    public function __construct(
        public ?Box $box,
        public PackingSource $source,
    ) {
    }

    public function toArray(): array
    {
        return [
            'box' => $this->box?->toArray(),
            'packable' => $this->box !== null,
            'source' => $this->source->value,
        ];
    }
}
