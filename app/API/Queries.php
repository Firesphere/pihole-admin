<?php

namespace App\API;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Queries extends APIBase
{
    public function getAll(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();

        $q = $this->createFTLQuery($params);

        $api = new CallAPI();
        $data = $api->doCall($q);
        if (array_key_exists('FTLnotrunning', $data)) {
            return $this->returnAsJSON($request, $response, $data);
        }
        $return = ['data' => []];

        // This is a bit unwieldy, but I don't see a way around it
        foreach ($data as $line) {
            $list = explode(' ', $line);
            // UTF-8 encode domain
            $list[2] = utf8_encode(str_replace('~', ' ', $list[2]));
            // UTF-8 encode client host name
            $list[3] = utf8_encode($list[3]);
            $list[11] = str_replace('"', '', $list[11]);

            $return['data'][] = $list;
        }

        return $this->returnAsJSON($request, $response, $return);
    }

    /**
     * Check all the options and build the correct
     * FTL Query from it
     * @param $params
     * @return string
     */
    private function createFTLQuery($params)
    {
        $base = 'getallqueries';
        $q = '';
        $param = '';

        if (isset($params['from'], $params['until'])) {
            // Get within a time period
            $q = '-time';
            $param = sprintf('%s %s', $params['from'], $params['until']);
        }

        if (isset($params['domain'])) {
            // Get specific domain only
            $q = '-domain';
            $param = $params['domain'];
        }

        if (isset($params['client'])) {
            // Get specific client only
            $q = '-client';
            if (isset($params['type']) && $params['type'] === 'blocked') {
                $q .= '-blocked';
            }
            $param = $params['client'];
        }

        if (isset($params['querytype'])) {
            // Get specific query type only
            $q = '-qtype';
            $param = $params['querytype'];
        }

        if (isset($params['forwarddest'])) {
            // Get specific forward destination only
            $q = '-forward';
            $param = $params['forwarddest'];
        }

        if (isset($params['limit']) && is_numeric($params['limit'])) {
            // Limit the amount of results
            $param = sprintf('(%d)', $params['limit']);
        }

        // Get all queries
        return sprintf('%s%s %s', $base, $q, $param);
    }
}
