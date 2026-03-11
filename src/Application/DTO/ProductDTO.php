<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Model\Product;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProductDTO
{
    public function __construct(
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
        #[Assert\NotNull(message: 'Weight is required.')]
        #[Assert\Type(type: 'numeric', message: 'Weight must be a number.')]
        #[Assert\Positive(message: 'Weight must be positive.')]
        #[Assert\LessThanOrEqual(value: ValidationLimits::MAX_WEIGHT, message: 'Weight must not exceed {{ compared_value }}.')]
        public float $weight,
    ) {
    }

    public function toDomainProduct(): Product
    {
        return new Product(
            width: $this->width,
            height: $this->height,
            length: $this->length,
            weight: $this->weight,
        );
    }
}
