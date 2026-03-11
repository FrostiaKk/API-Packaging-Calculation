<?php

declare(strict_types=1);

namespace App\Domain\Model;

final readonly class PackingInput
{
    /**
     * @param Product[] $products
     */
    public function __construct(
        public array $products,
    ) {
    }

    public function totalWeight(): float
    {
        return round(array_sum(array_map(fn(Product $p) => $p->weight, $this->products)), 2);
    }

    public function totalVolume(): float
    {
        return array_sum(array_map(fn(Product $p) => $p->volume(), $this->products));
    }

    public function computeInputHash(): string
    {
        $normalizedProducts = array_map(
            fn(Product $p) => array_map(fn(float $v) => round($v, 2), $p->normalizedDimensions()),
            $this->products,
        );

        usort($normalizedProducts, function (array $a, array $b): int {
            for ($i = 0; $i < 3; $i++) {
                $cmp = $a[$i] <=> $b[$i];
                if ($cmp !== 0) {
                    return $cmp;
                }
            }
            return 0;
        });

        $canonical = json_encode([
            'products' => $normalizedProducts,
            'totalWeight' => $this->totalWeight(),
        ], JSON_THROW_ON_ERROR);

        return hash('sha256', $canonical);
    }
}
