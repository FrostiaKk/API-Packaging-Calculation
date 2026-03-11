<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Http\ResponseFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ApiKeyAuthMiddleware
{
    private const PUBLIC_PATHS = ['/health'];

    public function __construct(
        private readonly string $apiKey,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function process(RequestInterface $request): ?ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (in_array($path, self::PUBLIC_PATHS, true)) {
            return null;
        }

        $providedKey = $request->getHeaderLine('X-Api-Key');

        if ($providedKey === '' || !hash_equals($this->apiKey, $providedKey)) {
            return $this->responseFactory->json(401, [
                'error' => 'unauthorized',
                'message' => 'Missing or invalid API key.',
            ]);
        }

        return null;
    }
}
