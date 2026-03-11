<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Handler;

use App\Application\Handler\PackingHandler;
use App\Domain\Exception\NotPackableException;
use App\Domain\Exception\ValidationException;
use App\Domain\Model\Box;
use App\Domain\Model\PackingOutcome;
use App\Domain\Model\PackingSource;
use App\Application\Http\ResponseFactory;
use App\Application\Validation\RequestBodyParser;
use App\Domain\Service\PackingServiceInterface;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class PackingHandlerTest extends TestCase
{
    private PackingServiceInterface&MockObject $packingService;
    private PackingHandler $handler;

    protected function setUp(): void
    {
        $this->packingService = $this->createMock(PackingServiceInterface::class);
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $parser = new RequestBodyParser($validator);

        $this->handler = new PackingHandler($this->packingService, $parser, new ResponseFactory());
    }

    public function testValidRequestReturns200(): void
    {
        $this->packingService->method('pack')
            ->willReturn(new PackingOutcome(
                box: new Box(1, 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d', 5.0, 5.0, 5.0, 20.0),
                source: PackingSource::Api,
            ));

        $request = new Request('POST', '/pack', ['Content-Type' => 'application/json'], json_encode([
            'products' => [
                ['width' => 2.0, 'height' => 2.0, 'length' => 2.0, 'weight' => 5.0],
            ],
        ]));

        $response = $this->handler->handle($request);

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        self::assertTrue($body['packable']);
        self::assertSame('api', $body['source']);
        self::assertSame(1, $body['box']['id']);
    }

    public function testInvalidJsonThrowsJsonException(): void
    {
        $request = new Request('POST', '/pack', ['Content-Type' => 'application/json'], '{invalid');

        $this->expectException(\JsonException::class);

        $this->handler->handle($request);
    }

    public function testMissingProductsThrowsValidationException(): void
    {
        $request = new Request('POST', '/pack', ['Content-Type' => 'application/json'], json_encode([]));

        try {
            $this->handler->handle($request);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('products', $e->getErrors());
        }
    }

    public function testEmptyProductsArrayThrowsValidationException(): void
    {
        $request = new Request('POST', '/pack', ['Content-Type' => 'application/json'], json_encode([
            'products' => [],
        ]));

        try {
            $this->handler->handle($request);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertNotEmpty($e->getErrors());
        }
    }

    public function testNegativeDimensionsThrowsValidationException(): void
    {
        $request = new Request('POST', '/pack', ['Content-Type' => 'application/json'], json_encode([
            'products' => [
                ['width' => -1.0, 'height' => 2.0, 'length' => 2.0, 'weight' => 5.0],
            ],
        ]));

        try {
            $this->handler->handle($request);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertNotEmpty($e->getErrors());
        }
    }

    public function testNotPackablePropagatesException(): void
    {
        $this->packingService->method('pack')
            ->willThrowException(new NotPackableException());

        $request = new Request('POST', '/pack', ['Content-Type' => 'application/json'], json_encode([
            'products' => [
                ['width' => 2.0, 'height' => 2.0, 'length' => 2.0, 'weight' => 5.0],
            ],
        ]));

        $this->expectException(NotPackableException::class);

        $this->handler->handle($request);
    }

    public function testZeroDimensionsThrowsValidationException(): void
    {
        $request = new Request('POST', '/pack', ['Content-Type' => 'application/json'], json_encode([
            'products' => [
                ['width' => 0, 'height' => 2.0, 'length' => 2.0, 'weight' => 5.0],
            ],
        ]));

        try {
            $this->handler->handle($request);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertNotEmpty($e->getErrors());
        }
    }
}
