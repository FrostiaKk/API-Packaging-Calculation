<?php

declare(strict_types=1);

namespace App\Application\ReadModel;

use App\Domain\Model\PackingOutcome;

final readonly class PackingOutcomeReadModel
{
    private function __construct(
        public ?BoxReadModel $box,
        public bool $packable,
        public string $source,
    ) {
    }

    public static function fromDomainModel(PackingOutcome $outcome): self
    {
        return new self(
            box: $outcome->box !== null ? BoxReadModel::fromDomainModel($outcome->box) : null,
            packable: $outcome->box !== null,
            source: $outcome->source->value,
        );
    }

    public function toArray(): array
    {
        return [
            'box' => $this->box?->toArray(),
            'packable' => $this->packable,
            'source' => $this->source,
        ];
    }
}
