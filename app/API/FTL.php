<?php

namespace App\API;

use App\PiHole;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

class FTL
{
    public function startstop(RequestInterface $request, ResponseInterface $response, $args)
    {
        $id = false;
        $allowedActions = ['enable', 'disable'];
        $requestParts = explode('/', $request->getUri()->getPath());
        foreach ($requestParts as $key => $value) {
            if (in_array($value, $allowedActions)) {
                $id = $key;
                break;
            }
        }
        if ($id === false) {
            $body = $request->getBody();
            $body->write('Action not allowed');
            throw new HttpBadRequestException($request, E_USER_ERROR);
        }
        $escaped = escapeshellcmd($requestParts[$id]);
        $output = null;
        $return_status = -1;
        if ($escaped === 'disable' && $args['time']) {
            $escaped = sprintf('disable %ds', (int)$args['time']);
        }
        $command = 'sudo pihole ' . $escaped;
        exec($command, $output, $return_status);
        if ($return_status !== 0) {
            throw new \RuntimeException("Executing {$command} failed.", E_USER_WARNING);
        }

        return $output;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws \Exception
     */
    public function summary(ServerRequestInterface $request, ResponseInterface $response)
    {
        $path = strtolower($request->getUri()->getPath());
        $raw = str_contains($path, 'raw');
        $API = new CallAPI();
        $stats = $API->doCall('stats');
        $data = $this->formatStats($stats, $raw);
        $data['gravity_last_updated'] = PiHole::gravity_last_update($raw);
        $body = $response->getBody();
        $body->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getMaxlogage(ServerRequestInterface $request, ResponseInterface $response)
    {
        $API = new CallAPI();
        $data = $API->doCall('maxlogage');
        if (array_key_exists('FTLnotrunning', $data)) {
            return $data;
        }
        // Convert seconds to hours and rounds to one decimal place.
        $age = round((int)$data[0] / 3600, 1);
        // Return 24h if value is 0, empty, null or non numeric.
        $age = $age ?: 24;

        $body = $response->getBody();
        $body->write(json_encode(['maxlogage' => $age]));

        return $response->withHeader('Content-Type', 'application/json');

    }

    /**
     * Convert the stats from the socket to an associative array
     * @param array $data
     * @param bool $raw
     * @return array
     */
    private function formatStats($data, $raw = false)
    {
        $return = [];
        foreach ($data as $line) {
            [$key, $value] = explode(' ', $line);
            // Exception for status, which is a string. All the others are numeric
            if ($key === 'status') {
                $return[$key] = $value;
                continue;
            }
            $value = (float)$value;
            if (!$raw) {
                // Format percentages to no decimals.
                $decimals = str_contains($key, 'percentage') ? 0 : 1;
                $value = number_format($value, $decimals, '.', '');
            }
            $return[$key] = $value;
        }

        return $return;
    }
}