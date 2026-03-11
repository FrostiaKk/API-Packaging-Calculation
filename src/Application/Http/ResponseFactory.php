<?php

declare(strict_types=1);

namespace App\Application\Http;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class ResponseFactory
{
    public function json(int $status, array $data): ResponseInterface
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }

    public function noContent(): ResponseInterface
    {
        return new Response(204);
    }
}
