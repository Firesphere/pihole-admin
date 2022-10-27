<?php

namespace App\API;

use App\DB\SQLiteDB;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PiholeDB extends APIBase
{

    public function getMinTimestamp(RequestInterface $request, ResponseInterface $response)
    {
        $db = new SQLiteDB('FTLDB');
        $results = $db->doQuery('SELECT MIN(timestamp) FROM queries');
        $return = [];
        if (!is_bool($results)) {
            $return = ['mintimestamp' => $results->fetchArray()[0]];
        }

        return $this->returnAsJSON($request, $response, $return);
    }

    public function getGraphData(RequestInterface $request, ResponseInterface $response)
    {
        $db = new SQLiteDB('FTLDB');

        $limit = '';
        parse_str($request->getUri()->getQuery(), $params);

        if (isset($params['from'], $params['until'])) {
            $limit = 'timestamp >= :from AND timestamp <= :until';
        } elseif (isset($params['from']) && !isset($params['until'])) {
            $limit = 'timestamp >= :from';
        } elseif (!isset($params['from']) && isset($params['until'])) {
            $limit = 'timestamp <= :until';
        }

        $interval = 600;

        if (isset($params['interval'])) {
            $q = (int)$params['interval'];
            if ($q >= 10) {
                $interval = $q;
            }
        }

        // Round $from and $until to match the requested $interval
        $from = (int)((int)$params['from'] / $interval) * $interval;
        $until = (int)((int)$params['until'] / $interval) * $interval;

        // Count domains and blocked queries using the same intervals
        $sqlcommand = "
        SELECT
            (timestamp / :interval) * :interval AS interval,
            SUM(CASE
                WHEN status !=0 THEN 1
                ELSE 0
            END) AS domains,
            SUM(CASE
                WHEN status IN (1,4,5,6,7,8,9,10,11,15,16) THEN 1
                ELSE 0
            END) AS blocked
        FROM queries
        WHERE $limit
        GROUP BY interval
        ORDER BY interval";

        $queryParams = [
            ':from'     => $from,
            ':until'    => $until,
            ':interval' => $interval,
        ];
        $results = $db->doQuery($sqlcommand, $queryParams);

        $data = $this->parseDBData($results, $interval, $from, $until);

        return $this->returnAsJSON($request, $response, $data);
    }

    /**
     * Parse the DB result into graph data, filling in missing interval sections with zero
     * @param \SQLite3Result $results
     * @param $interval
     * @param $from
     * @param $until
     * @return array[]
     */
    private function parseDBData($results, $interval, $from, $until)
    {
        $domains = [];
        $blocked = [];
        $first_db_timestamp = -1;

        if (!is_bool($results)) {
            // Read in the data
            while ($row = $results->fetchArray()) {
                $domains[$row['interval']] = (int)$row['domains'];
                $blocked[$row['interval']] = (int)$row['blocked'];
                if ($first_db_timestamp === -1) {
                    $first_db_timestamp = (int)$row[0];
                }
            }
        }

        // It is unpredictable what the first timestamp returned by the database will be.
        // This depends on live data. The bar graph can handle "gaps", but the Area graph can't.
        // Hence, we filling the "missing" timeslots with 0 to avoid wrong graphic render.
        // (https://github.com/pi-hole/AdminLTE/pull/2374#issuecomment-1261865428)
        $aligned_from = $from + (($first_db_timestamp - $from) % $interval);

        // Fill gaps in returned data
        for ($i = $aligned_from; $i < $until; $i += $interval) {
            if (!array_key_exists($i, $domains)) {
                $domains[$i] = 0;
                $blocked[$i] = 0;
            }
        }

        return ['domains_over_time' => $domains, 'ads_over_time' => $blocked];
    }
}