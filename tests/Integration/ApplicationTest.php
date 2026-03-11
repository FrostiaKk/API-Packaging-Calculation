<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Kernel;
use App\Application\Handler\BoxHandler;
use App\Application\Handler\HealthHandler;
use App\Application\Handler\PackingHandler;
use App\Application\Http\ResponseFactory;
use App\Application\Middleware\ApiKeyAuthMiddleware;
use App\Domain\Exception\NotPackableException;
use App\Domain\Model\Box;
use App\Domain\Model\PackingOutcome;
use App\Domain\Model\PackingSource;
use App\Domain\Port\HealthCheckInterface;
use App\Application\Validation\RequestBodyParser;
use App\Domain\Service\BoxServiceInterface;
use App\Domain\Service\PackingServiceInterface;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Validation;

final class ApplicationTest extends TestCase
{
    private const TEST_API_KEY = 'test-key-123';

    private PackingServiceInterface&MockObject $packingService;
    private BoxServiceInterface&MockObject $boxService;
    private HealthCheckInterface&MockObject $healthCheck;
    private Kernel $app;

    protected function setUp(): void
    {
        $this->packingService = $this->createMock(PackingServiceInterface::class);
        $this->boxService = $this->createMock(BoxServiceInterface::class);
        $this->healthCheck = $this->createMock(HealthCheckInterface::class);
        $this->healthCheck->method('isDatabaseHealthy')->willReturn(true);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $parser = new RequestBodyParser($validator);

        $responseFactory = new ResponseFactory();

        $packingHandler = new PackingHandler($this->packingService, $parser, $responseFactory);
        $boxHandler = new BoxHandler($this->boxService, $parser, $responseFactory);
        $healthHandler = new HealthHandler($this->healthCheck, $responseFactory);

        $this->app = new Kernel(
            $packingHandler,
            $boxHandler,
            new NullLogger(),
            new ApiKeyAuthMiddleware(self::TEST_API_KEY, $responseFactory),
            $healthHandler,
            $responseFactory,
        );
    }

    private function authHeaders(array $extra = []): array
    {
        return array_merge(['X-Api-Key' => self::TEST_API_KEY], $extra);
    }

    public function testPostPackReturnsBoxOnSuccess(): void
    {
        $this->packingService->method('pack')
            ->willReturn(new PackingOutcome(
                box: new Box(1, 5.0, 5.0, 5.0, 20.0),
                source: PackingSource::Api,
            ));

        $request = new Request('POST', '/pack', $this->authHeaders(['Content-Type' => 'application/json']), json_encode([
            'products' => [
                ['width' => 2.0, 'height' => 2.0, 'length' => 2.0, 'weight' => 5.0],
            ],
        ]));

        $response = $this->app->run($request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        self::assertTrue($body['packable']);
        self::assertSame(1, $body['box']['id']);
    }

    public function testPostPackReturns400OnInvalidJson(): void
    {
        $request = new Request('POST', '/pack', $this->authHeaders(['Content-Type' => 'application/json']), 'not json');

        $response = $this->app->run($request);

        self::assertSame(400, $response->getStatusCode());
    }

    public function testPostPackReturns422WhenNotPackable(): void
    {
        $this->packingService->method('pack')
            ->willThrowException(new NotPackableException());

        $request = new Request('POST', '/pack', $this->authHeaders(['Content-Type' => 'application/json']), json_encode([
            'products' => [
                ['width' => 2.0, 'height' => 2.0, 'length' => 2.0, 'weight' => 5.0],
            ],
        ]));

        $response = $this->app->run($request);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testGetHealthReturnsOkWithoutAuth(): void
    {
        $request = new Request('GET', '/health');
        $response = $this->app->run($request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        self::assertSame('ok', $body['status']);
        self::assertTrue($body['checks']['database']);
    }

    public function testGetHealthReturns503WhenDatabaseDown(): void
    {
        $healthCheck = $this->createMock(HealthCheckInterface::class);
        $healthCheck->method('isDatabaseHealthy')->willReturn(false);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $parser = new RequestBodyParser($validator);
        $responseFactory = new ResponseFactory();

        $healthHandler = new HealthHandler($healthCheck, $responseFactory);

        $app = new Kernel(
            new PackingHandler($this->packingService, $parser, $responseFactory),
            new BoxHandler($this->boxService, $parser, $responseFactory),
            new NullLogger(),
            new ApiKeyAuthMiddleware(self::TEST_API_KEY, $responseFactory),
            $healthHandler,
            $responseFactory,
        );

        $request = new Request('GET', '/health');
        $response = $app->run($request);

        self::assertSame(503, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        self::assertSame('degraded', $body['status']);
        self::assertFalse($body['checks']['database']);
    }

    public function testUnknownRouteReturns404(): void
    {
        $request = new Request('GET', '/nonexistent', $this->authHeaders());
        $response = $this->app->run($request);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testWrongMethodReturns405(): void
    {
        $request = new Request('GET', '/pack', $this->authHeaders());
        $response = $this->app->run($request);

        self::assertSame(405, $response->getStatusCode());
    }

    public function testGetBoxesReturnsListOfBoxes(): void
    {
        $this->boxService->method('listAll')
            ->willReturn([
                new Box(1, 5.0, 5.0, 5.0, 20.0),
                new Box(2, 9.0, 9.0, 9.0, 30.0),
            ]);

        $request = new Request('GET', '/boxes', $this->authHeaders());
        $response = $this->app->run($request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        self::assertCount(2, $body);
    }

    public function testInternalErrorReturns500(): void
    {
        $this->packingService->method('pack')
            ->willThrowException(new \RuntimeException('Unexpected'));

        $request = new Request('POST', '/pack', $this->authHeaders(['Content-Type' => 'application/json']), json_encode([
            'products' => [
                ['width' => 2.0, 'height' => 2.0, 'length' => 2.0, 'weight' => 5.0],
            ],
        ]));

        $response = $this->app->run($request);

        self::assertSame(500, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        self::assertSame('internal_error', $body['error']);
    }

    public function testMissingApiKeyReturns401(): void
    {
        $request = new Request('POST', '/pack', ['Content-Type' => 'application/json'], json_encode([
            'products' => [
                ['width' => 2.0, 'height' => 2.0, 'length' => 2.0, 'weight' => 5.0],
            ],
        ]));

        $response = $this->app->run($request);

        self::assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        self::assertSame('unauthorized', $body['error']);
    }

    public function testInvalidApiKeyReturns401(): void
    {
        $request = new Request('POST', '/pack', ['X-Api-Key' => 'wrong-key', 'Content-Type' => 'application/json'], json_encode([
            'products' => [
                ['width' => 2.0, 'height' => 2.0, 'length' => 2.0, 'weight' => 5.0],
            ],
        ]));

        $response = $this->app->run($request);

        self::assertSame(401, $response->getStatusCode());
    }

    public function testResponseIncludesRequestId(): void
    {
        $request = new Request('GET', '/health');
        $response = $this->app->run($request);

        self::assertNotEmpty($response->getHeaderLine('X-Request-Id'));
    }

    public function testResponsePreservesProvidedRequestId(): void
    {
        $request = new Request('GET', '/health', ['X-Request-Id' => 'test-id-123']);
        $response = $this->app->run($request);

        self::assertSame('test-id-123', $response->getHeaderLine('X-Request-Id'));
    }
}
