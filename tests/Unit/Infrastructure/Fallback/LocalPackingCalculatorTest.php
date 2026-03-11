<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Fallback;

use App\Domain\Model\Box;
use App\Domain\Model\PackingInput;
use App\Domain\Model\Product;
use App\Infrastructure\Fallback\LocalPackingCalculator;
use PHPUnit\Framework\TestCase;

final class LocalPackingCalculatorTest extends TestCase
{
    private LocalPackingCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new LocalPackingCalculator();
    }

    public function testSingleProductFitsSmallestBox(): void
    {
        $input = new PackingInput([
            new Product(2.0, 2.0, 2.0, 5.0),
        ]);

        $boxes = [
            new Box(1, 9.0, 9.0, 9.0, 30.0),   // large
            new Box(2, 3.0, 3.0, 3.0, 10.0),     // smallest that fits
            new Box(3, 5.0, 5.0, 5.0, 20.0),     // medium
        ];

        $result = $this->calculator->calculate($input, $boxes);

        self::assertNotNull($result);
        self::assertSame(2, $result->id);
    }

    public function testProductTooLargeForAnyBox(): void
    {
        $input = new PackingInput([
            new Product(10.0, 1.0, 1.0, 1.0),
        ]);

        $boxes = [
            new Box(1, 3.0, 3.0, 3.0, 30.0),
            new Box(2, 5.0, 5.0, 5.0, 30.0),
        ];

        $result = $this->calculator->calculate($input, $boxes);

        self::assertNull($result);
    }

    public function testWeightExceedsAllBoxes(): void
    {
        $input = new PackingInput([
            new Product(1.0, 1.0, 1.0, 50.0),
        ]);

        $boxes = [
            new Box(1, 5.0, 5.0, 5.0, 10.0),
            new Box(2, 9.0, 9.0, 9.0, 30.0),
        ];

        $result = $this->calculator->calculate($input, $boxes);

        self::assertNull($result);
    }

    public function testVolumeExceedsBox(): void
    {
        // Two cubes of 3x3x3 = total volume 54, box 4x4x4 = volume 64
        // But individually each cube fits (3 <= 4 on all dims)
        // This should pass volume and dimension checks
        $input = new PackingInput([
            new Product(3.0, 3.0, 3.0, 1.0),
            new Product(3.0, 3.0, 3.0, 1.0),
        ]);

        $boxes = [
            new Box(1, 4.0, 4.0, 4.0, 10.0), // volume 64, fits 54
        ];

        $result = $this->calculator->calculate($input, $boxes);

        self::assertNotNull($result);
        self::assertSame(1, $result->id);
    }

    public function testProductRotationIsConsidered(): void
    {
        // Product 1x1x8 should fit in box 2x2x10 (sorted: 1,1,8 vs 2,2,10)
        $input = new PackingInput([
            new Product(1.0, 8.0, 1.0, 1.0),
        ]);

        $boxes = [
            new Box(1, 2.0, 2.0, 10.0, 10.0),
        ];

        $result = $this->calculator->calculate($input, $boxes);

        self::assertNotNull($result);
    }

    public function testLongProductDoesNotFitCube(): void
    {
        // Product 10x1x1 should NOT fit in box 3x3x3
        // Even though volume 10 < 27, the longest dimension 10 > 3
        $input = new PackingInput([
            new Product(10.0, 1.0, 1.0, 1.0),
        ]);

        $boxes = [
            new Box(1, 3.0, 3.0, 3.0, 30.0),
        ];

        $result = $this->calculator->calculate($input, $boxes);

        self::assertNull($result);
    }

    public function testEmptyBoxListReturnsNull(): void
    {
        $input = new PackingInput([
            new Product(1.0, 1.0, 1.0, 1.0),
        ]);

        $result = $this->calculator->calculate($input, []);

        self::assertNull($result);
    }

    public function testMultipleProductsCombinedWeight(): void
    {
        $input = new PackingInput([
            new Product(1.0, 1.0, 1.0, 15.0),
            new Product(1.0, 1.0, 1.0, 10.0),
        ]);

        $boxes = [
            new Box(1, 5.0, 5.0, 5.0, 20.0),  // weight 25 > 20, too light
            new Box(2, 5.0, 5.0, 5.0, 30.0),  // weight 25 <= 30, fits
        ];

        $result = $this->calculator->calculate($input, $boxes);

        self::assertNotNull($result);
        self::assertSame(2, $result->id);
    }
}
