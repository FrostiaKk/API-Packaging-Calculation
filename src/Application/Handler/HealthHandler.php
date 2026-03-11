<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Http\ResponseFactory;
use App\Domain\Port\HealthCheckInterface;
use Psr\Http\Message\ResponseInterface;

final class HealthHandler
{
    public function __construct(
        private readonly HealthCheckInterface $healthCheck,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function check(): ResponseInterface
    {
        $checks = ['database' => $this->healthCheck->isDatabaseHealthy()];
        $healthy = !in_array(false, $checks, true);

        return $this->responseFactory->json(
            $healthy ? 200 : 503,
            ['status' => $healthy ? 'ok' : 'degraded', 'checks' => $checks],
        );
    }
}
