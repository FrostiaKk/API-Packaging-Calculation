<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\DTO\PackingRequestDTO;
use App\Application\DTO\ProductDTO;
use App\Application\Http\ResponseFactory;
use App\Application\ReadModel\PackingOutcomeReadModel;
use App\Application\Validation\RequestBodyParser;
use App\Domain\Exception\ValidationException;
use App\Domain\Service\PackingServiceInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PackingHandler
{
    public function __construct(
        private readonly PackingServiceInterface $packingService,
        private readonly RequestBodyParser $requestBodyParser,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        $packingRequest = $this->parseAndValidate($request);
        $domainInput = $packingRequest->toDomainInput();
        $outcome = $this->packingService->pack($domainInput);

        return $this->responseFactory->json(200, PackingOutcomeReadModel::fromDomainModel($outcome)->toArray());
    }

    private function parseAndValidate(RequestInterface $request): PackingRequestDTO
    {
        /** @var PackingRequestDTO */
        return $this->requestBodyParser->parseAndValidate(
            $request,
            [],
            [],
            fn(array $data) => $this->buildPackingRequest($data),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildPackingRequest(array $data): PackingRequestDTO
    {
        if (!array_key_exists('products', $data)) {
            throw new ValidationException(
                ['products' => 'Products field is required.'],
            );
        }

        if (!is_array($data['products'])) {
            throw new ValidationException(
                ['products' => 'Products must be an array.'],
            );
        }

        $products = [];
        $allErrors = [];

        foreach ($data['products'] as $index => $item) {
            if (!is_array($item)) {
                $allErrors["products[{$index}]"] = 'Each product must be an object.';
                continue;
            }

            $fieldErrors = [];
            foreach (['width', 'height', 'length', 'weight'] as $field) {
                if (!array_key_exists($field, $item)) {
                    $fieldErrors["products[{$index}].{$field}"] = ucfirst($field) . ' is required.';
                } elseif (!is_numeric($item[$field])) {
                    $fieldErrors["products[{$index}].{$field}"] = ucfirst($field) . ' must be a number.';
                }
            }

            if (!empty($fieldErrors)) {
                $allErrors = array_merge($allErrors, $fieldErrors);
                continue;
            }

            $products[] = new ProductDTO(
                width: (float) $item['width'],
                height: (float) $item['height'],
                length: (float) $item['length'],
                weight: (float) $item['weight'],
            );
        }

        if (!empty($allErrors)) {
            throw new ValidationException($allErrors);
        }

        return new PackingRequestDTO(products: $products);
    }
}
