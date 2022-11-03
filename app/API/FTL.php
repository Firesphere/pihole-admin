<?php

namespace App\API;

use App\API\Gravity\Gravity;
use App\Helper\Helper;
use App\PiHole;
use Exception;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;

class FTL extends APIBase
{
    public static function getFTLStatus()
    {
        $api = new CallAPI();
        $port = $api->doCall('dns-port');

        // Retrieve FTL status
        $FTLstats = $api->doCall('stats');

        if (array_key_exists('FTLnotrunning', $port) || array_key_exists('FTLnotrunning', $FTLstats)) {
            // FTL is not running
            return -1;
        }
        if (in_array('status enabled', $FTLstats, false)) {
            // FTL is enabled
            if ((int)$port[0] <= 0) {
                // Port=0; FTL is not listening
                return -1;
            }

            // FTL is running on this port
            return (int)$port[0];
        }
        if (in_array('status disabled', $FTLstats, false)) {
            // FTL is disabled
            return 0;
        }

        // All options exhausted, it's dead
        return -2;
    }

    /**
     * Start or Stop FTL
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param $args
     * @return ResponseInterface
     * @throws JsonException
     */
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
            throw new HttpBadRequestException($request, E_USER_ERROR);
        }
        $escaped = escapeshellcmd($requestParts[$id]);
        if ($escaped === 'disable' && $args['time']) {
            $escaped = sprintf('disable %ds', (int)$args['time']);
        }
        try {
            $result = PiHole::execute($escaped);
            $return = ['success' => true, 'response' => $result];
        } catch (RuntimeException $e) {
            $this->returnAsJSON($request, $response, ['success' => false, 'status' => Helper::returnJSONError($e->getMessage())]);
        }

        $return['status'] = str_contains($escaped, 'enable') ? 'enabled' : 'disabled';


        return $this->returnAsJSON($request, $response, $return);
    }


    /**
     * Get the summary of data from FTL
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     */
    public function summary(ServerRequestInterface $request, ResponseInterface $response)
    {
        $path = strtolower($request->getUri()->getPath());
        $raw = str_contains($path, 'raw');
        $API = new CallAPI();
        $stats = $API->doCall('stats');
        $data = $this->formatStats($stats, $raw);
        $data['gravity_last_updated'] = Gravity::gravity_last_update($raw);
        $query = $request->getUri()->getQuery();
        // Check for extras that need to be included from params
        parse_str($query, $params);
        if (isset($params['topItems'])) {
            $topItems = $this->getTopItems($API, $params);
            $data = array_merge($data, $topItems);
        }
        if (isset($params['option'])) {
            foreach ($params['option'] as $query => $value) {
                $method = str_replace('Blocked', '', $query);
                $callResult = $this->$method($API, $query, $value);
                $data = array_merge($data, $callResult);
            }
        }

        return $this->returnAsJSON($request, $response, $data);
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
     * @param $API
     * @param $params
     * @return array|bool[]
     */
    protected function getTopItems($API, $params)
    {
        switch (true) {
            case $params['topItems'] === 'audit':
                $call = ' for audit';
                break;
            case is_numeric($params['topItems']):
                $call = sprintf(' (%d)', $params['topItems']);
                break;
            default:
                $call = '';
        }
        $items['top_queries'] = $API->doCall(sprintf('top-domains%s', $call));
        $items['top_ads'] = $API->doCall(sprintf('top-ads%s', $call));


        if (array_key_exists('FTLnotrunning', $items)) {
            return ['FTLnotrunning' => true];
        }
        $return = [];
        foreach ($items as $type => $lines) {
            foreach ($lines as $line) {
                $opt = '';
                [$key, $count, $domain] = explode(' ', $line);
                if (substr_count($line, ' ') === 3) {
                    [$key, $count, $domain, $opt] = explode(' ', $line);
                }
                if (!empty($opt)) {
                    $domain = sprintf('%s (%s)', $domain, $opt);
                }
                $domain = utf8_encode($domain);
                $return[$type][$domain] = (int)$count;
            }
        }

        return $return;
    }

    /**
     * Get the query data over time, blocked and permitted
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array|ResponseInterface|null
     * @throws JsonException
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

        return $this->returnAsJSON($request, $response, $return);
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
                $over_time[(int)$tmp[0]][$i] = (float)$tmp[$i + 1];
            }
        }

        return array_merge($result, ['over_time' => $over_time]);
    }

    protected function getClientNames($API)
    {
        $data = $API->doCall('client-names');
        if (array_key_exists('FTLnotrunning', $data)) {
            return ['FTLnotrunning' => true];
        }
        $client_names = [];
        foreach ($data as $line) {
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
     * @throws JsonException
     */
    public function getMaxlogage(ServerRequestInterface $request, ResponseInterface $response)
    {
        $API = new CallAPI();
        $data = $API->doCall('maxlogage');
        if (array_key_exists('FTLnotrunning', $data)) {
            return $this->returnAsJSON($request, $response, $data);
        }
        // Convert seconds to hours and rounds to one decimal place.
        $age = round((int)$data[0] / 3600, 1);
        // Return 24h if value is 0, empty, null or non numeric.
        $age = $age ?: 24;

        return $this->returnAsJSON($request, $response, ['maxlogage' => $age]);
    }

    /**
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return bool[]|ResponseInterface
     * @throws JsonException
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

        return $this->returnAsJSON($request, $response, ['querytypes' => $querytypes]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return bool[]|ResponseInterface
     * @throws JsonException
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
        arsort($forward_dest);

        return $this->returnAsJSON($request, $response, ['forward_destinations' => $forward_dest]);
    }

    /**
     * Stub for semantics
     * @param $API
     * @param $method
     * @param $limit
     * @return array[]|bool[]
     */
    protected function getQuerySources($API, $method, $limit = 0)
    {
        return $this->getQuerySourceLists($API, $method, $limit);
    }

    /**
     * Fetch a result list based on the given method and limit
     * @param $API
     * @param $method
     * @param $limit
     * @return array[]|bool[]
     */
    private function getQuerySourceLists($API, $method, $limit)
    {
        $queryOptions = [
            'getQuerySources'   => ['top-clients', 'top_sources'],
            'topClientsBlocked' => ['top-clients blocked', 'top_sources_blocked'],
        ];
        $str = '';
        if ($limit > 0) {
            $str = sprintf(' (%d)', $limit);
        }
        $data = $API->doCall($queryOptions[$method][0] . $str);

        if (array_key_exists('FTLnotrunning', $data)) {
            return ['FTLnotrunning' => true];
        }

        $top_clients = [];
        foreach ($data as $line) {
            $tmp = explode(' ', $line);
            $clientip = utf8_encode($tmp[2]);
            if (count($tmp) > 3 && !empty($tmp[3])) {
                $clientname = utf8_encode($tmp[3]);
                $top_clients[$clientname . '|' . $clientip] = (int)$tmp[1];
            } else {
                $top_clients[$clientip] = (int)$tmp[1];
            }
        }

        return [$queryOptions[$method][1] => $top_clients];
    }

    /**
     * Stub for semantics
     * @param $API
     * @param $method
     * @param $limit
     * @return array[]|bool[]
     */
    protected function topClients($API, $method, $limit = 0)
    {
        return $this->getQuerySourceLists($API, $method, $limit);
    }

    public function tailLog(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();

        if (isset($params['FTL'])) {
            $file = fopen('/var/log/pihole/FTL.log', 'rb');
        } else {
            $file = fopen('/var/log/pihole/pihole.log', 'rb');
        }

        if (!$file) {
            return $this->returnAsJSON(
                $request,
                $response,
                [
                    'offset'  => 0,
                    'lines' => ["Failed to open log file. Check permissions!\n"]
                ]
            );
        }

        $offset = $params['offset'] ?? -1;
        $lines = [];
        if ($offset > 0) {
            // Seeks on the file pointer where we want to continue reading is known
            fseek($file, $offset);
            $ftell = ftell($file);

            while (!feof($file)) {
                $data = trim(fgets($file));
                if (!empty($data)) {
                    $lines[] = Helper::formatLine(fgets($file));
                }
            }
        } else {
            fseek($file, $offset, SEEK_END);
            $ftell = ftell($file)+1;
        }



        return $this->returnAsJSON($request, $response, ['offset' => $ftell, 'lines' => $lines]);
    }
}
