<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Handler\BoxHandler;
use App\Application\Handler\HealthHandler;
use App\Application\Handler\PackingHandler;
use App\Application\Http\ResponseFactory;
use App\Application\Middleware\ApiKeyAuthMiddleware;
use App\Domain\Exception\DomainInvariantException;
use App\Domain\Exception\NotPackableException;
use App\Domain\Exception\ValidationException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

use function FastRoute\simpleDispatcher;

final class Kernel
{
    private Dispatcher $dispatcher;

    public function __construct(
        private readonly PackingHandler $packingHandler,
        private readonly BoxHandler $boxHandler,
        private readonly LoggerInterface $logger,
        private readonly ApiKeyAuthMiddleware $authMiddleware,
        private readonly HealthHandler $healthHandler,
        private readonly ResponseFactory $responseFactory,
    ) {
        $this->dispatcher = $this->createDispatcher();
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        $requestId = $request->getHeaderLine('X-Request-Id') ?: Uuid::uuid4()->toString();

        $response = $this->handleRequest($request, $requestId);

        return $response->withHeader('X-Request-Id', $requestId);
    }

    private function handleRequest(RequestInterface $request, string $requestId): ResponseInterface
    {
        try {
            $authResponse = $this->authMiddleware->process($request);
            if ($authResponse !== null) {
                return $authResponse;
            }

            return $this->dispatch($request);
        } catch (ValidationException $e) {
            return $this->responseFactory->json(400, [
                'error' => 'validation_error',
                'message' => $e->getMessage(),
                'details' => $e->getErrors(),
            ]);
        } catch (DomainInvariantException $e) {
            return $this->responseFactory->json(400, [
                'error' => 'domain_error',
                'message' => $e->getMessage(),
            ]);
        } catch (NotPackableException $e) {
            return $this->responseFactory->json(422, [
                'error' => 'not_packable',
                'message' => $e->getMessage(),
            ]);
        } catch (\JsonException) {
            return $this->responseFactory->json(400, [
                'error' => 'invalid_json',
                'message' => 'Malformed JSON in request body.',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Unhandled exception.', [
                'request_id' => $requestId,
                'method' => $request->getMethod(),
                'path' => $request->getUri()->getPath(),
                'exception' => $e,
            ]);

            return $this->responseFactory->json(500, [
                'error' => 'internal_error',
                'message' => 'An unexpected error occurred.',
            ]);
        }
    }

    private function dispatch(RequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        $routeInfo = $this->dispatcher->dispatch($method, $path);

        return match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => $this->responseFactory->json(404, [
                'error' => 'not_found',
                'message' => 'Route not found.',
            ]),
            Dispatcher::METHOD_NOT_ALLOWED => $this->responseFactory->json(405, [
                'error' => 'method_not_allowed',
                'message' => 'Method not allowed. Allowed: ' . implode(', ', $routeInfo[1]),
            ]),
            Dispatcher::FOUND => $this->handleRoute($routeInfo[1], $routeInfo[2], $request),
        };
    }

    private function handleRoute(callable $handler, array $vars, RequestInterface $request): ResponseInterface
    {
        return $handler($request, $vars);
    }

    private function createDispatcher(): Dispatcher
    {
        return simpleDispatcher(function (RouteCollector $r) {
            $r->addRoute('POST', '/pack', function (RequestInterface $request) {
                return $this->packingHandler->handle($request);
            });

            $r->addRoute('GET', '/boxes', function () {
                return $this->boxHandler->list();
            });

            $r->addRoute('GET', '/boxes/{id:\d+}', function (RequestInterface $request, array $vars) {
                return $this->boxHandler->get((int) $vars['id']);
            });

            $r->addRoute('POST', '/boxes', function (RequestInterface $request) {
                return $this->boxHandler->create($request);
            });

            $r->addRoute('PUT', '/boxes/{id:\d+}', function (RequestInterface $request, array $vars) {
                return $this->boxHandler->update((int) $vars['id'], $request);
            });

            $r->addRoute('DELETE', '/boxes/{id:\d+}', function (RequestInterface $request, array $vars) {
                return $this->boxHandler->delete((int) $vars['id']);
            });

            $r->addRoute('GET', '/health', function () {
                return $this->healthHandler->check();
            });
        });
    }
}
