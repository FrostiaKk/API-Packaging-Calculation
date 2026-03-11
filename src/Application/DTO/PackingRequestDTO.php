<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Model\PackingInput;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PackingRequestDTO
{
    /**
     * @param ProductDTO[] $products
     */
    public function __construct(
        #[Assert\NotNull(message: 'Products list is required.')]
        #[Assert\Count(
            min: 1,
            max: ValidationLimits::MAX_PRODUCTS,
            minMessage: 'At least one product is required.',
            maxMessage: 'Cannot pack more than {{ limit }} products at once.',
        )]
        #[Assert\Valid]
        public array $products,
    ) {
    }

    public function toDomainInput(): PackingInput
    {
        return new PackingInput(
            products: array_map(
                fn(ProductDTO $p) => $p->toDomainProduct(),
                $this->products,
            ),
        );
    }
}
