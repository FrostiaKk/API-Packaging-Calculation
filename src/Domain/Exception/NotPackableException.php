<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class NotPackableException extends \RuntimeException
{
    public function __construct(string $message = 'Products cannot be packed into a single available box.')
    {
        parent::__construct($message);
    }
}
