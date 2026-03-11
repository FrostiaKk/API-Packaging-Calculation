<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class CalculationException extends \RuntimeException
{
    public function __construct(string $message = 'Packing calculation failed.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
