<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Exception\NotPackableException;
use App\Domain\Model\Box;
use App\Domain\Model\PackingInput;

interface PackingCalculatorInterface
{
    /**
     * @param Box[] $availableBoxes
     * @throws NotPackableException when items definitively cannot fit into any available box
     */
    public function calculate(PackingInput $input, array $availableBoxes): ?Box;
}
