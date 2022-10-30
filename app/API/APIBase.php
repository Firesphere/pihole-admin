<?php

namespace App\API;

use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class APIBase
{
    /**
     * Return the requested data as JSON
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $data
     * @return ResponseInterface
     * @throws JsonException
     */
    protected function returnAsJSON(RequestInterface $request, ResponseInterface $response, $data): ResponseInterface
    {
        $body = $response->getBody();
        $body->write(json_encode($data, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
