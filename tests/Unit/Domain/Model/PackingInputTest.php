<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\PackingInput;
use App\Domain\Model\Product;
use PHPUnit\Framework\TestCase;

final class PackingInputTest extends TestCase
{
    public function testTotalWeightSumsAllProducts(): void
    {
        $input = new PackingInput([
            new Product(1.0, 1.0, 1.0, 5.0),
            new Product(1.0, 1.0, 1.0, 7.0),
        ]);

        self::assertSame(12.0, $input->totalWeight());
    }

    public function testTotalVolumeCalculatesCorrectly(): void
    {
        $input = new PackingInput([
            new Product(2.0, 3.0, 4.0, 1.0), // volume = 24
            new Product(1.0, 1.0, 1.0, 1.0), // volume = 1
        ]);

        self::assertSame(25.0, $input->totalVolume());
    }

    public function testInputHashIsDeterministic(): void
    {
        $input = new PackingInput([
            new Product(3.0, 2.0, 1.0, 5.0),
            new Product(4.0, 3.0, 2.0, 7.0),
        ]);

        $hash1 = $input->computeInputHash();
        $hash2 = $input->computeInputHash();

        self::assertSame($hash1, $hash2);
        self::assertSame(64, strlen($hash1));
    }

    public function testInputHashIsOrderIndependent(): void
    {
        $input1 = new PackingInput([
            new Product(3.0, 2.0, 1.0, 5.0),
            new Product(4.0, 3.0, 2.0, 7.0),
        ]);

        $input2 = new PackingInput([
            new Product(4.0, 3.0, 2.0, 7.0),
            new Product(3.0, 2.0, 1.0, 5.0),
        ]);

        self::assertSame($input1->computeInputHash(), $input2->computeInputHash());
    }

    public function testInputHashIsRotationIndependent(): void
    {
        $input1 = new PackingInput([
            new Product(3.0, 2.0, 1.0, 5.0),
        ]);

        // Same dimensions, different order (rotated)
        $input2 = new PackingInput([
            new Product(1.0, 3.0, 2.0, 5.0),
        ]);

        self::assertSame($input1->computeInputHash(), $input2->computeInputHash());
    }

    public function testInputHashCombinesWeights(): void
    {
        // Same dimensions, different individual weights but same total
        $input1 = new PackingInput([
            new Product(3.0, 2.0, 1.0, 7.0),
            new Product(4.0, 3.0, 2.0, 5.0),
        ]);

        $input2 = new PackingInput([
            new Product(3.0, 2.0, 1.0, 3.0),
            new Product(4.0, 3.0, 2.0, 9.0),
        ]);

        self::assertSame($input1->computeInputHash(), $input2->computeInputHash());
    }

    public function testInputHashDiffersForDifferentDimensions(): void
    {
        $input1 = new PackingInput([
            new Product(3.0, 2.0, 1.0, 5.0),
        ]);

        $input2 = new PackingInput([
            new Product(4.0, 2.0, 1.0, 5.0),
        ]);

        self::assertNotSame($input1->computeInputHash(), $input2->computeInputHash());
    }

    public function testInputHashDiffersForDifferentTotalWeights(): void
    {
        $input1 = new PackingInput([
            new Product(3.0, 2.0, 1.0, 5.0),
        ]);

        $input2 = new PackingInput([
            new Product(3.0, 2.0, 1.0, 10.0),
        ]);

        self::assertNotSame($input1->computeInputHash(), $input2->computeInputHash());
    }
}
