<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\PackingRequestDTO;
use App\Application\DTO\ProductDTO;
use App\Domain\Model\PackingInput;
use PHPUnit\Framework\TestCase;

final class PackingRequestDTOTest extends TestCase
{
    public function testToDomainInputConvertsAllProducts(): void
    {
        $dto = new PackingRequestDTO([
            new ProductDTO(1.0, 2.0, 3.0, 5.0),
            new ProductDTO(4.0, 5.0, 6.0, 7.0),
        ]);

        $input = $dto->toDomainInput();

        self::assertInstanceOf(PackingInput::class, $input);
        self::assertCount(2, $input->products);
        self::assertSame(1.0, $input->products[0]->width);
        self::assertSame(7.0, $input->products[1]->weight);
    }
}
