<?php

declare(strict_types=1);

namespace App\Infrastructure\Api;

use App\Domain\Exception\CalculationException;
use App\Domain\Exception\NotPackableException;
use App\Domain\Model\Box;
use App\Domain\Model\PackingInput;
use App\Domain\Port\PackingCalculatorInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ApiPackingCalculator implements PackingCalculatorInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $username,
        private readonly string $apiKey,
        private readonly string $baseUrl,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function calculate(PackingInput $input, array $availableBoxes): ?Box
    {
        $payload = $this->buildPayload($input, $availableBoxes);

        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . '/packIntoMany', [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => 2,
                'connect_timeout' => 2,
            ]);
        } catch (GuzzleException $e) {
            $this->logger->error('Packing API request failed.', ['exception' => $e]);
            throw new CalculationException('Packing API request failed: ' . $e->getMessage(), $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            $this->logger->error('Packing API returned non-200 status.', ['statusCode' => $statusCode]);
            throw new CalculationException("Packing API returned HTTP {$statusCode}.");
        }

        try {
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error('Packing API returned invalid JSON.', ['exception' => $e]);
            throw new CalculationException('Packing API returned invalid JSON: ' . $e->getMessage(), $e);
        }

        return $this->parseResponse($body, $availableBoxes);
    }

    /** @param Box[] $availableBoxes */
    private function buildPayload(PackingInput $input, array $availableBoxes): array
    {
        $items = [];
        foreach ($input->products as $index => $product) {
            $items[] = [
                'id' => 'product_' . $index,
                'w' => $product->width,
                'h' => $product->height,
                'd' => $product->length,
                'wg' => $product->weight,
                'vr' => 1,
                'q' => 1,
            ];
        }

        $bins = [];
        foreach ($availableBoxes as $box) {
            $bins[] = [
                'id' => (string) $box->id,
                'w' => $box->width,
                'h' => $box->height,
                'd' => $box->length,
                'max_wg' => $box->maxWeight,
            ];
        }

        return [
            'username' => $this->username,
            'api_key' => $this->apiKey,
            'bins' => $bins,
            'items' => $items,
            'params' => [
                'optimization_mode' => 'bins_number',
            ],
        ];
    }

    /** @param Box[] $availableBoxes */
    private function parseResponse(array $body, array $availableBoxes): ?Box
    {
        $response = $body['response'] ?? [];

        $status = $response['status'] ?? 0;
        if ($status !== 1) {
            $this->logger->error('Packing API returned error status.', [
                'status' => $status,
                'errors' => $response['errors'] ?? [],
            ]);
            throw new CalculationException('Packing API returned critical error.');
        }

        $notPackedItems = $response['not_packed_items'] ?? [];
        if (!empty($notPackedItems)) {
            $this->logger->info('Items cannot fit into any available box.', [
                'not_packed_count' => count($notPackedItems),
            ]);
            throw new NotPackableException('One or more items cannot fit into any available box.');
        }

        $binsPacked = $response['bins_packed'] ?? [];

        if (count($binsPacked) === 0) {
            $this->logger->error('Packing API returned zero bins packed.');
            throw new CalculationException('Packing API returned success but no bins were packed.');
        }

        if (count($binsPacked) > 1) {
            $this->logger->info('Items require multiple boxes, single-box packing not possible.', [
                'bins_packed_count' => count($binsPacked),
            ]);
            return null;
        }

        $binId = (string) ($binsPacked[0]['bin_data']['id'] ?? '');

        foreach ($availableBoxes as $box) {
            if ((string) $box->id === $binId) {
                return $box;
            }
        }

        $this->logger->error('Packing API returned unknown bin ID.', [
            'returned_bin_id' => $binId,
            'available_ids' => array_map(fn(Box $b) => $b->id, $availableBoxes),
        ]);

        throw new CalculationException("Packing API returned unknown bin ID: {$binId}.");
    }
}
