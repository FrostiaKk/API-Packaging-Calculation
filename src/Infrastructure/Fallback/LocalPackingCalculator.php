<?php

declare(strict_types=1);

namespace App\Infrastructure\Fallback;

use App\Domain\Model\Box;
use App\Domain\Model\PackingInput;
use App\Domain\Port\PackingCalculatorInterface;

/**
 * Conservative fallback: may suggest a larger box than optimal, but never one that's too small.
 */
final class LocalPackingCalculator implements PackingCalculatorInterface
{
    public function calculate(PackingInput $input, array $availableBoxes): ?Box
    {
        $totalWeight = $input->totalWeight();
        $totalVolume = $input->totalVolume();

        $candidates = $availableBoxes;
        usort($candidates, fn(Box $a, Box $b) => $a->volume() <=> $b->volume());

        foreach ($candidates as $box) {
            if ($this->fitsInBox($input, $box, $totalWeight, $totalVolume)) {
                return $box;
            }
        }

        return null;
    }

    private function fitsInBox(
        PackingInput $input,
        Box $box,
        float $totalWeight,
        float $totalVolume,
    ): bool {
        if ($totalWeight > $box->maxWeight) {
            return false;
        }

        if ($totalVolume > $box->volume()) {
            return false;
        }

        $boxDims = $box->normalizedDimensions();

        foreach ($input->products as $product) {
            $productDims = $product->normalizedDimensions();
            if ($productDims[0] > $boxDims[0] || $productDims[1] > $boxDims[1] || $productDims[2] > $boxDims[2]) {
                return false;
            }
        }

        return true;
    }
}
