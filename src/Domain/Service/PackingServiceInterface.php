<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Exception\NotPackableException;
use App\Domain\Model\PackingInput;
use App\Domain\Model\PackingOutcome;

interface PackingServiceInterface
{
    /**
     * @throws NotPackableException
     */
    public function pack(PackingInput $input): PackingOutcome;
}
