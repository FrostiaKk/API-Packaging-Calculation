<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Api;

use App\Domain\Exception\CalculationException;
use App\Domain\Exception\NotPackableException;
use App\Domain\Model\Box;
use App\Domain\Model\PackingInput;
use App\Domain\Model\Product;
use App\Infrastructure\Api\ApiPackingCalculator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ApiPackingCalculatorTest extends TestCase
{
    private function createCalculator(MockHandler $mock): ApiPackingCalculator
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        return new ApiPackingCalculator(
            httpClient: $client,
            username: 'test-user',
            apiKey: 'test-api-key',
            baseUrl: 'https://api.example.com/packer',
            logger: new NullLogger(),
        );
    }

    private function createInput(): PackingInput
    {
        return new PackingInput([
            new Product(2.0, 2.0, 2.0, 5.0),
        ]);
    }

    private function createBoxes(): array
    {
        return [
            new Box(1, 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d', 5.0, 5.0, 5.0, 20.0),
            new Box(2, 'b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e', 9.0, 9.0, 9.0, 30.0),
        ];
    }

    private function buildApiResponse(array $binsPacked = [], array $notPackedItems = [], int $status = 1): array
    {
        return [
            'response' => [
                'bins_packed' => $binsPacked,
                'not_packed_items' => $notPackedItems,
                'errors' => [],
                'status' => $status,
            ],
        ];
    }

    public function testSuccessfulApiCallReturnsBestBox(): void
    {
        $apiResponse = $this->buildApiResponse(
            binsPacked: [
                [
                    'bin_data' => [
                        'id' => '1',
                        'w' => 5,
                        'h' => 5,
                        'd' => 5,
                        'used_space' => 64.0,
                        'weight' => 5.0,
                    ],
                    'items' => [
                        [
                            'id' => 'product_0',
                            'w' => 2,
                            'h' => 2,
                            'd' => 2,
                            'wg' => 5.0,
                            'coordinates' => ['x1' => 0, 'y1' => 0, 'z1' => 0, 'x2' => 2, 'y2' => 2, 'z2' => 2],
                        ],
                    ],
                ],
            ],
        );

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($apiResponse)),
        ]);

        $calculator = $this->createCalculator($mock);
        $result = $calculator->calculate($this->createInput(), $this->createBoxes());

        self::assertNotNull($result);
        self::assertSame(1, $result->id);
    }

    public function testMultipleBinsPackedReturnsNull(): void
    {
        $apiResponse = $this->buildApiResponse(
            binsPacked: [
                ['bin_data' => ['id' => '1', 'w' => 5, 'h' => 5, 'd' => 5, 'used_space' => 50.0, 'weight' => 3.0], 'items' => []],
                ['bin_data' => ['id' => '2', 'w' => 9, 'h' => 9, 'd' => 9, 'used_space' => 20.0, 'weight' => 2.0], 'items' => []],
            ],
        );

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($apiResponse)),
        ]);

        $calculator = $this->createCalculator($mock);
        $result = $calculator->calculate($this->createInput(), $this->createBoxes());

        self::assertNull($result);
    }

    public function testEmptyBinsPackedThrowsCalculationException(): void
    {
        $apiResponse = $this->buildApiResponse(binsPacked: []);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($apiResponse)),
        ]);

        $calculator = $this->createCalculator($mock);

        $this->expectException(CalculationException::class);
        $this->expectExceptionMessage('Packing API returned success but no bins were packed.');

        $calculator->calculate($this->createInput(), $this->createBoxes());
    }

    public function testNotPackedItemsThrowsNotPackableException(): void
    {
        $apiResponse = $this->buildApiResponse(
            notPackedItems: [
                ['id' => 'product_0', 'w' => 2, 'h' => 2, 'd' => 2, 'q' => 1, 'wg' => 5.0],
            ],
        );

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($apiResponse)),
        ]);

        $calculator = $this->createCalculator($mock);

        $this->expectException(NotPackableException::class);
        $this->expectExceptionMessage('One or more items cannot fit into any available box.');

        $calculator->calculate($this->createInput(), $this->createBoxes());
    }

    public function testCriticalApiStatusThrowsCalculationException(): void
    {
        $apiResponse = $this->buildApiResponse(status: 0);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($apiResponse)),
        ]);

        $calculator = $this->createCalculator($mock);

        $this->expectException(CalculationException::class);
        $this->expectExceptionMessage('Packing API returned critical error.');

        $calculator->calculate($this->createInput(), $this->createBoxes());
    }

    public function testApiErrorThrowsCalculationException(): void
    {
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $calculator = $this->createCalculator($mock);

        $this->expectException(CalculationException::class);

        $calculator->calculate($this->createInput(), $this->createBoxes());
    }

    public function testConnectionErrorThrowsCalculationException(): void
    {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\ConnectException(
                'Connection refused',
                new \GuzzleHttp\Psr7\Request('POST', 'https://api.example.com/packer/packIntoMany'),
            ),
        ]);

        $calculator = $this->createCalculator($mock);

        $this->expectException(CalculationException::class);

        $calculator->calculate($this->createInput(), $this->createBoxes());
    }

    public function testRateLimitThrowsCalculationException(): void
    {
        $mock = new MockHandler([
            new Response(429, [], 'Rate limit exceeded'),
        ]);

        $calculator = $this->createCalculator($mock);

        $this->expectException(CalculationException::class);

        $calculator->calculate($this->createInput(), $this->createBoxes());
    }

    public function testMalformedJsonResponseThrowsCalculationException(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], 'not valid json{{{'),
        ]);

        $calculator = $this->createCalculator($mock);

        $this->expectException(CalculationException::class);
        $this->expectExceptionMessage('Packing API returned invalid JSON');

        $calculator->calculate($this->createInput(), $this->createBoxes());
    }

    public function testUnknownBinIdThrowsCalculationException(): void
    {
        $apiResponse = $this->buildApiResponse(
            binsPacked: [
                [
                    'bin_data' => [
                        'id' => '999',
                        'w' => 5,
                        'h' => 5,
                        'd' => 5,
                        'used_space' => 64.0,
                        'weight' => 5.0,
                    ],
                    'items' => [],
                ],
            ],
        );

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($apiResponse)),
        ]);

        $calculator = $this->createCalculator($mock);

        $this->expectException(CalculationException::class);
        $this->expectExceptionMessage('Packing API returned unknown bin ID: 999.');

        $calculator->calculate($this->createInput(), $this->createBoxes());
    }
}
