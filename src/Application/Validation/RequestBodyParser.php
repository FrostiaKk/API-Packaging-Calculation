<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Domain\Exception\ValidationException;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestBodyParser
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @template T of object
     * @param string[] $requiredFields
     * @param callable(array<string, mixed>): T $dtoFactory
     * @return T
     * @throws ValidationException
     * @throws \JsonException
     */
    public function parseAndValidate(
        RequestInterface $request,
        array $requiredFields,
        callable $dtoFactory,
    ): object {
        $data = $this->parseJson($request);
        $this->checkRequiredNumericFields($data, $requiredFields);

        $dto = $dtoFactory($data);

        $this->validateDto($dto);

        return $dto;
    }

    /**
     * @return array<string, mixed>
     * @throws \JsonException
     * @throws ValidationException
     */
    private function parseJson(RequestInterface $request): array
    {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new ValidationException(
                ['body' => 'Request body must be a JSON object.'],
            );
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $fields
     * @throws ValidationException
     */
    private function checkRequiredNumericFields(array $data, array $fields): void
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                $errors[$field] = ucfirst($field) . ' is required.';
            } elseif (!is_numeric($data[$field])) {
                $errors[$field] = ucfirst($field) . ' must be a number.';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateDto(object $dto): void
    {
        $violations = $this->validator->validate($dto);

        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = (string) $violation->getMessage();
            }
            throw new ValidationException($errors);
        }
    }
}
