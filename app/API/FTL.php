<?php

namespace App\API;

use App\API\Gravity\Gravity;
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
        if ($escaped === 'disable' && $args['time']) {
            $escaped = sprintf('disable %ds', (int)$args['time']);
        }

        return PiHole::execute($escaped);
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
        $data['gravity_last_updated'] = Gravity::gravity_last_update($raw);
        $body = $response->getBody();
        $body->write(json_encode($data));

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

    /**
     * Get the query data over time, blocked and permitted
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array|ResponseInterface|null
     * @throws \JsonException
     */
    public function overTimeData(ServerRequestInterface $request, ResponseInterface $response)
    {
        $query = $request->getUri()->getQuery();
        parse_str($query, $params);
        $API = new CallAPI();
        if ($params['type'] === 'period') {
            $return = $this->getDataPeriod($API, $params);
        } elseif ($params['type'] === 'clients') {
            $return = $this->getClientsPeriod($API, $params);
        }


        $response->getBody()->write(json_encode($return, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param CallAPI $API
     * @param array $params
     * @return array[]|mixed
     */
    protected function getDataPeriod($API, $params)
    {
        $data = $API->doCall(sprintf('overTime%s', $params['option'][0]));
        if (array_key_exists('FTLnotrunning', $data)) {
            return $data;
        }
        $domains_over_time = [];
        $ads_over_time = [];
        foreach ($data as $line) {
            [$time, $domains, $ads] = explode(' ', $line);
            $domains_over_time[$time] = (int)$domains;
            $ads_over_time[$time] = (int)$ads;
        }

        return [
            'domains_over_time' => $domains_over_time,
            'ads_over_time'     => $ads_over_time,
        ];
    }

    /**
     * @param $API
     * @param $params
     * @return array
     */
    protected function getClientsPeriod($API, $params)
    {
        if (isset($params['option'][0]) && $params['option'][0] === 'getClientNames') {
            $result = $this->getClientNames($API);
        }
        $data = $API->doCall('ClientsoverTime');

        if (array_key_exists('FTLnotrunning', $data)) {
            return ['FTLnotrunning' => true];
        }
        $over_time = [];
        foreach ($data as $line) {
            $tmp = explode(' ', $line);
            for ($i = 0; $i < count($tmp) - 1; ++$i) {
                $over_time[intval($tmp[0])][$i] = floatval($tmp[$i + 1]);
            }
        }

        return array_merge($result, ['over_time' => $over_time]);

    }

    protected function getClientNames($API)
    {
        $return = $API->doCall('client-names');
        if (array_key_exists('FTLnotrunning', $return)) {
            return ['FTLnotrunning' => true];
        }
        $client_names = [];
        foreach ($return as $line) {
            [$name, $ip] = explode(' ', $line);
            $client_names[] = [
                'name' => utf8_encode($name),
                'ip'   => utf8_encode($ip),
            ];
        }

        return ['clients' => $client_names];
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array|ResponseInterface|null
     * @throws \JsonException
     */
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
        $body->write(json_encode(['maxlogage' => $age], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return bool[]|ResponseInterface
     * @throws \JsonException
     */
    public function getQueryTypes(ServerRequestInterface $request, ResponseInterface $response)
    {
        $API = new CallAPI();
        $data = $API->doCall('querytypes');

        if (array_key_exists('FTLnotrunning', $data)) {
            return ['FTLnotrunning' => true];
        }
        $querytypes = [];
        foreach ($data as $ret) {
            if (empty($ret)) {
                continue;
            }
            [$type, $value] = explode(': ', $ret);
            // Reply cannot contain non-ASCII characters
            $querytypes[$type] = (float)$value;
        }

        $body = $response->getBody();
        $body->write(json_encode(['querytypes' => $querytypes], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return bool[]|ResponseInterface
     * @throws \JsonException
     */
    public function getUpstreams(ServerRequestInterface $request, ResponseInterface $response)
    {
        $api = new CallAPI();
        $return = $api->doCall('forward-names');

        if (array_key_exists('FTLnotrunning', $return)) {
            return ['FTLnotrunning' => true];
        }
        $forward_dest = [];
        foreach ($return as $line) {
            [$key, $count, $ip, $name] = explode(' ', $line);
            $forwardip = utf8_encode($ip);
            $forwardname = utf8_encode($name);
            $destKey = $forwardip;
            if (!empty($forwardname) && !empty($forwardip)) {
                $destKey = sprintf('%s|%s', $forwardname, $forwardip);
            }
            $forward_dest[$destKey] = (float)($count);
        }

        $body = $response->getBody();
        $body->write(json_encode(['forward_destinations' => arsort($forward_dest)], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}