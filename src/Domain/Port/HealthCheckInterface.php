<?php

declare(strict_types=1);

namespace App\Domain\Port;

interface HealthCheckInterface
{
    public function isDatabaseHealthy(): bool;
}
