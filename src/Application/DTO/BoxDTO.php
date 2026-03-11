<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Model\Box;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class BoxDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'External ID is required.')]
        #[Assert\Type(type: 'string', message: 'External ID must be a string.')]
        #[Assert\Uuid(message: 'External ID must be a valid UUID.')]
        public string $externalId,
        #[Assert\NotNull(message: 'Width is required.')]
        #[Assert\Type(type: 'numeric', message: 'Width must be a number.')]
        #[Assert\Positive(message: 'Width must be positive.')]
        #[Assert\LessThanOrEqual(value: ValidationLimits::MAX_DIMENSION, message: 'Width must not exceed {{ compared_value }}.')]
        public float $width,
        #[Assert\NotNull(message: 'Height is required.')]
        #[Assert\Type(type: 'numeric', message: 'Height must be a number.')]
        #[Assert\Positive(message: 'Height must be positive.')]
        #[Assert\LessThanOrEqual(value: ValidationLimits::MAX_DIMENSION, message: 'Height must not exceed {{ compared_value }}.')]
        public float $height,
        #[Assert\NotNull(message: 'Length is required.')]
        #[Assert\Type(type: 'numeric', message: 'Length must be a number.')]
        #[Assert\Positive(message: 'Length must be positive.')]
        #[Assert\LessThanOrEqual(value: ValidationLimits::MAX_DIMENSION, message: 'Length must not exceed {{ compared_value }}.')]
        public float $length,
        #[Assert\NotNull(message: 'Max weight is required.')]
        #[Assert\Type(type: 'numeric', message: 'Max weight must be a number.')]
        #[Assert\Positive(message: 'Max weight must be positive.')]
        #[Assert\LessThanOrEqual(value: ValidationLimits::MAX_WEIGHT, message: 'Max weight must not exceed {{ compared_value }}.')]
        public float $maxWeight,
    ) {
    }

    public function toDomainModel(?int $id = null): Box
    {
        return new Box(
            id: $id,
            externalId: $this->externalId,
            width: $this->width,
            height: $this->height,
            length: $this->length,
            maxWeight: $this->maxWeight,
        );
    }
}
