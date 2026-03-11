<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\DTO\BoxDTO;
use App\Application\Http\ResponseFactory;
use App\Application\ReadModel\BoxReadModel;
use App\Application\Validation\RequestBodyParser;
use App\Domain\Model\Box;
use App\Domain\Service\BoxServiceInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class BoxHandler
{
    public function __construct(
        private readonly BoxServiceInterface $boxService,
        private readonly RequestBodyParser $requestBodyParser,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function list(): ResponseInterface
    {
        $boxes = $this->boxService->listAll();

        return $this->responseFactory->json(
            200,
            array_map(fn(Box $b) => BoxReadModel::fromDomainModel($b)->toArray(), $boxes),
        );
    }

    public function get(int $id): ResponseInterface
    {
        $box = $this->boxService->getById($id);
        if ($box === null) {
            return $this->responseFactory->json(404, ['error' => 'not_found', 'message' => 'Box not found.']);
        }

        return $this->responseFactory->json(200, BoxReadModel::fromDomainModel($box)->toArray());
    }

    public function create(RequestInterface $request): ResponseInterface
    {
        $dto = $this->parseAndValidateBox($request);
        $created = $this->boxService->create($dto->toDomainModel());

        return $this->responseFactory->json(201, BoxReadModel::fromDomainModel($created)->toArray());
    }

    public function update(int $id, RequestInterface $request): ResponseInterface
    {
        $dto = $this->parseAndValidateBox($request);
        $updated = $this->boxService->update($id, $dto->toDomainModel($id));

        if ($updated === null) {
            return $this->responseFactory->json(404, ['error' => 'not_found', 'message' => 'Box not found.']);
        }

        return $this->responseFactory->json(200, BoxReadModel::fromDomainModel($updated)->toArray());
    }

    public function delete(int $id): ResponseInterface
    {
        $deleted = $this->boxService->delete($id);
        if (!$deleted) {
            return $this->responseFactory->json(404, ['error' => 'not_found', 'message' => 'Box not found.']);
        }

        return $this->responseFactory->noContent();
    }

    private function parseAndValidateBox(RequestInterface $request): BoxDTO
    {
        /** @var BoxDTO */
        return $this->requestBodyParser->parseAndValidate(
            $request,
            ['width', 'height', 'length', 'maxWeight'],
            ['externalId'],
            fn(array $data) => new BoxDTO(
                externalId: (string) $data['externalId'],
                width: (float) $data['width'],
                height: (float) $data['height'],
                length: (float) $data['length'],
                maxWeight: (float) $data['maxWeight'],
            ),
        );
    }
}
