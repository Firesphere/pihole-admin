<?php

namespace App\API;

use App\DB\SQLiteDB;
use App\Frontend\Frontend;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SQLite3Result;

/**
 * @todo clean up some things here
 */
class PiholeDB extends APIBase
{
    /**
     * @var SQLiteDB
     */
    protected $db;

    public function __construct()
    {
        $this->db = new SQLiteDB('FTLDB', SQLITE3_OPEN_READWRITE);
    }


    public function status(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();

        $extra = ';';
        if (isset($params['ignore']) && $params['ignore'] === 'DNSMASQ_WARN') {
            $extra = "WHERE type != 'DNSMASQ_WARN';";
        }
        $query = sprintf('SELECT COUNT(*) FROM message %s', $extra);
        $results = $this->db->doQuery($query);

        if (!is_bool($results)) {
            return $this->returnAsJSON($request, $response, ['message_count' => $results->fetchArray()[0]]);
        }

        return $this->returnAsJSON($request, $response, []);
    }


    public function getMessages(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        $extra = '';
        if (isset($params['ignore']) && $params['ignore'] === 'DNSMASQ_WARN') {
            $extra = "WHERE type != 'DNSMASQ_WARN';";
        }

        $messages = array();
        $results = $this->db->doQuery(sprintf('SELECT * FROM message %s', $extra));

        while ($res = $results->fetchArray(SQLITE3_ASSOC)) {
            // Convert string to to UTF-8 encoding to ensure php-json can handle it.
            // Furthermore, convert special characters to HTML entities to prevent XSS attacks.
            foreach ($res as $key => $value) {
                if (is_string($value)) {
                    $res[$key] = htmlspecialchars(utf8_encode($value));
                }
            }
            $messages[] = $res;
        }

        return $this->returnAsJSON($request, $response, ['messages' => $messages]);
    }

    public function deleteMessages(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getParsedBody();
        $ids = json_decode($params['id'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($ids)) {
            throw new \InvalidArgumentException('Invalid payload: id is not an array');
        }
        // Exploit prevention: Ensure all entries in the ID array are integers
        foreach ($ids as $value) {
            if (!is_numeric($value)) {
                throw new \InvalidArgumentException('Invalid payload: id contains non-numeric entries');
            }
        }
        $this->db->doQuery('DELETE FROM message WHERE id IN (:ids);', [':ids' => implode(',', $ids)]);

        return $this->returnAsJSON($request, $response, ['success' => true]);
    }

    public function getMinTimestamp(RequestInterface $request, ResponseInterface $response)
    {
        $results = $this->db->doQuery('SELECT MIN(timestamp) FROM queries');
        $return = [];
        if (!is_bool($results)) {
            $return = ['mintimestamp' => $results->fetchArray()[0]];
        }

        return $this->returnAsJSON($request, $response, $return);
    }

    /**
     * @throws JsonException
     */
    public function getGraphData(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        $limit = $this->getLimit($params);

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
        $results = $this->db->doQuery($sqlcommand, $queryParams);

        $data = $this->parseDBData($results, $interval, $from, $until);

        return $this->returnAsJSON($request, $response, $data);
    }

    /**
     * @param array $params
     * @return string
     */
    public function getLimit(array $params): string
    {
        $limitArr = [
            'from'  => '',
            'glue'  => '',
            'until' => ''
        ];

        if (isset($params['from'])) {
            $limitArr['from'] = 'timestamp >= :from';
        }
        if (isset($params['until'])) {
            $limitArr['until'] = 'timestamp <= :until';
        }
        if (isset($params['from'], $params['until'])) {
            $limitArr['glue'] = 'AND';
        }

        return trim(implode(' ', array_values($limitArr)));
    }

    /**
     * Parse the DB result into graph data, filling in missing interval sections with zero
     * @param SQLite3Result|bool $results
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
        // Hence, we're filling the "missing" timeslots with 0 to avoid wrong graphic render.
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

    /**
     * @throws JsonException
     */
    public function getQueryLogs(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        if (!isset($params['par'])) {
            $limit = $this->getLimit($params);

            // Use table "query_storage"
            //   - replace domain ID with domain
            //   - replace client ID with client name
            //   - replace forward ID with forward destination
            $dbquery = "
            SELECT timestamp, type,
            CASE typeof(domain) WHEN 'integer' 
                 THEN (
                     SELECT domain 
                     FROM domain_by_id d 
                     WHERE d.id = q.domain
                 ) 
                 ELSE 
                     domain 
                 END 
                 domain, 
             CASE typeof(client) WHEN 'integer' 
                 THEN (
                    SELECT CASE TRIM(name) WHEN ''
                        THEN 
                            c.ip 
                        ELSE 
                            c.name 
                        END name 
                    FROM client_by_id c 
                    WHERE c.id = q.client
                 ) 
                 ELSE 
                     client 
                 END 
                 client,
                CASE typeof(forward) WHEN 'integer' 
                    THEN (
                        SELECT forward 
                        FROM forward_by_id f 
                        WHERE f.id = q.forward
                        ) 
                    ELSE 
                        forward 
                    END 
                    forward, status, reply_type, reply_time, dnssec
                 FROM query_storage q
                 WHERE $limit";
            if (!empty($params['status'])) {
                // if some query status should be excluded
                $excludedStatus = $params['status'];
                if (preg_match('/^[0-9]+(?:,[0-9]+)*$/', $excludedStatus) === 1) {
                    // Append selector to DB query. The used regex ensures
                    // that only numbers, separated by commas are accepted
                    // to avoid code injection and other malicious things
                    // We accept only valid lists like "1,2,3"
                    // We reject ",2,3", "1,2," and similar arguments
                    $dbquery .= ' AND status NOT IN (' . $excludedStatus . ')';
                } else {
                    exit('Error. Selector status specified using an invalid format.');
                }
            }
            $dbquery .= ' ORDER BY timestamp ASC';
            $binds = [
                ':from'  => $params['from'] ?? 0,
                ':until' => $params['until'] ?? 0
            ];
            $results = $this->db->doQuery($dbquery, $binds);

            if (!is_bool($results)) {
                header('Content-Type: application/json');
                $first = true;
                echo '{"data":[';
                while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                    ob_start();
                    // If not the first row, echo a comma
                    echo(!$first ? ',' : '');
                    // Insert into array and output it in JSON format
                    echo json_encode([
                        $row['timestamp'],
                        Frontend::getQueryTypeString($row['type']),
                        utf8_encode(str_replace('~', ' ', $row['domain'])),
                        $row['client'],
                        $row['status'],
                        utf8_encode($row['forward'] ?? ''),
                        $row['reply_type'],
                        $row['reply_time'],
                        $row['dnssec']
                    ], JSON_THROW_ON_ERROR);
                    ob_end_flush();
                    $first = false;
                }
                echo "]}";
                exit;
            }
        }

        // The return arary is such an immense list, it requires a lot of memory

        return $this->returnAsJSON($request, $response, ['data' => []]);
    }

    public function getTopClients(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        $limit = $this->getLimit($params);

        $dbquery = "
SELECT CASE typeof(client) 
        WHEN 'integer' THEN (
            SELECT CASE TRIM(name) 
                WHEN '' THEN 
                    c.ip 
                ELSE 
                    c.name 
                END 
                name FROM client_by_id c 
                     WHERE c.id = q.client
            )
        ELSE 
              client 
        END 
        client, count(client)
            FROM query_storage q 
            WHERE $limit 
            GROUP BY client 
            ORDER BY count(client) 
            DESC LIMIT 20";

        $binds = [
            ':from'  => (int)$params['from'],
            ':until' => (int)$params['until']
        ];
        $results = $this->db->doQuery($dbquery, $binds);
        $counts = $this->getCounts($results);

        // Sort by number of hits
        arsort($counts);

        // Extract only the first ten entries
        $counts = array_slice($counts, 0, 10);

        return $this->returnAsJSON($request, $response, ['top_sources' => $counts]);
    }

    /**
     * @param bool|SQLite3Result $results
     * @return array
     */
    public function getCounts(bool|SQLite3Result $results): array
    {
        $counts = [];

        if ($results instanceof SQLite3Result) {
            while ($row = $results->fetchArray()) {
                $client = utf8_encode(strtolower($row[0]));
                // $row[0] is the client IP
                if (array_key_exists($client, $counts)) {
                    // Entry already exists, add to it
                    $counts[$client] += (int)$row[1];
                } else {
                    // Entry does not yet exist
                    $counts[$client] = (int)$row[1];
                }
            }
        }

        return $counts;
    }

    public function getTopAds(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        $limit = $this->getLimit($params);
        $binds = [
            ':from'  => (int)$params['from'],
            ':until' => (int)$params['until']
        ];

        $query = "
SELECT domain, count(domain)
    FROM queries 
    WHERE status IN (1,4,5,6,7,8,9,10,11) AND $limit 
    GROUP BY domain 
    ORDER BY count(domain) DESC LIMIT 10";

        $results = $this->db->doQuery(
            $query,
            $binds
        );

        $addomains = $this->getCounts($results);

        return $this->returnAsJSON($request, $response, ['top_ads' => $addomains]);
    }

    public function getTopDomains(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        $limit = $this->getLimit($params);
        $binds = [
            ':from'  => (int)$params['from'],
            ':until' => (int)$params['until']
        ];
        $query = "
SELECT domain, count(domain) 
    FROM queries 
    WHERE status IN (2,3,12,13,14) AND $limit
    GROUP BY domain 
    ORDER BY count(domain) DESC LIMIT 20";

        $results = $this->db->doQuery($query, $binds);
        $domains = $this->getCounts($results);

        // Sort by number of hits
        arsort($domains);

        // Extract only the first ten entries
        $domains = array_slice($domains, 0, 10);

        return $this->returnAsJSON($request, $response, ['top_domains' => $domains]);
    }
}
