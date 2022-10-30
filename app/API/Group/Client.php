<?php

namespace App\API\Group;

use App\API\GroupPostHandler;
use App\DB\SQLiteDB;
use App\Helper\Helper;

class Client extends GroupPostHandler
{
    /**
     * @var SQLiteDB
     */
    protected $gravity;

    public function __construct()
    {
        $this->gravity = new SQLiteDB('GRAVITYDB', SQLITE3_OPEN_READWRITE);
        $this->db = new SQLiteDB('FTLDB', SQLITE3_OPEN_READWRITE);
    }

    /**
     * @param $postData
     * @return array|array[]
     */
    public function getClients($postData)
    {
        // List all available groups
        $query = $this->gravity->doQuery('SELECT * FROM client;');

        $data = [];
        while ($res = $query->fetchArray(SQLITE3_ASSOC)) {
            $ftlQuery = 'SELECT name FROM network_addresses WHERE ip = :ip;';
            $params = [
                ':ip' => $res['ip']
            ];
            $ftlResult = $this->db->doQuery($ftlQuery, $params);

            // Check if got a hostname from the database. This may not be the case if the client is
            // specified by MAC address, a hostname or via a more general selector like an interface.
            $name_result = $ftlResult->fetchArray(SQLITE3_ASSOC);
            if (!is_bool($name_result)) {
                $res['name'] = $name_result['name'];
            } else {
                // Check if we can get a host name from the database when looking up the MAC
                // address of this client instead.
                $q = 'SELECT name 
                        FROM network n 
                            JOIN network_addresses na ON na.network_id = n.id 
                        WHERE hwaddr=:hwaddr COLLATE NOCASE AND name IS NOT NULL;';
                $result = $this->db->doQuery($q, [':hwaddr' => $res['ip']]);

                // Check if we found a result. There may be multiple entries for
                // this client in the network_addresses table. We use the first
                // hostname we find for the sake of simplicity.
                $name_result = $result->fetchArray(SQLITE3_ASSOC);
                if (!is_bool($name_result)) {
                    $res['name'] = $name_result['name'];
                } else {
                    $res['name'] = null;
                }
            }

            $groups = [];
            $groupQuery = sprintf('SELECT group_id FROM client_by_group WHERE client_id = %s;', $res['id']);
            $groupResult = $this->gravity->doQuery($groupQuery);

            while ($gres = $groupResult->fetchArray(SQLITE3_ASSOC)) {
                $groups[] = $gres['group_id'];
            }
            $res['groups'] = $groups;
            $data[] = $res;
        }

        return ['data' => $data];
    }

    /**
     * @param $postData
     * @return array
     */
    public function getUnconfiguredClients($postData): array
    {
        $query = 'SELECT DISTINCT id,hwaddr,macVendor FROM network ORDER BY firstSeen DESC;';
        $query = $this->db->doQuery($query);

        // Loop over results
        $ips = [];
        while ($res = $query->fetchArray(SQLITE3_ASSOC)) {
            $id = $res['id'];

            // Get possibly associated IP addresses and hostnames for this client
            $query_ips = "SELECT ip,name FROM network_addresses WHERE network_id = :id ORDER BY lastSeen DESC;";
            $result = $this->db->doQuery($query_ips, [':id' => $id]);
            $addresses = [];
            $names = [];
            while ($res_ips = $result->fetchArray(SQLITE3_ASSOC)) {
                $addresses[] = utf8_encode($res_ips['ip']);
                if ($res_ips['name'] !== null) {
                    $names[] = utf8_encode($res_ips['name']);
                }
            }

            // Prepare extra information
            $extrainfo = [];
            if (count($names)) {
                $hostnameStr = count($names) === 1 ? 'hostname' : 'hostnames';
                // Add list of associated host names to info string (if available)
                $extrainfo[] = sprintf('%s: %s;', $hostnameStr, implode(', ', $names));
            }

            // Add device vendor to info string (if available)
            if ($res['macVendor'] !== '') {
                $extrainfo[] = sprintf('vendor: %s;', htmlspecialchars((string)$res['macVendor']));
            }

            // Add list of associated host names to info string (if available and if this is not a mock device)
            if (stripos($res['hwaddr'], 'ip-') === false) {
                $addressStr = count($addresses) === 1 ? 'address' : 'addresses';
                if (count($addresses)) {
                    $extrainfo[] = sprintf('%s: %s', $addressStr, implode(', ', $addresses));
                }
            }

            $ips[strtoupper($res['hwaddr'])] = implode(' ', $extrainfo);
        }

        $query = $this->gravity->doQuery('SELECT ip FROM client;');

        // Loop over results, remove already configured clients
        while ($res = $query->fetchArray(SQLITE3_ASSOC)) {
            if (isset($ips[$res['ip']])) {
                unset($ips[$res['ip']]);
            }
            $key = 'IP-' . $res['ip'];
            if (isset($ips[$key])) {
                unset($ips[$key]);
            }
        }

        return $ips;
    }

    /**
     * @param $postData
     * @return array|bool[]
     */
    public function addClient($postData)
    {
        $ips = explode(' ', trim($postData['ip']));
        $total = count($ips);
        $added = 0;

        foreach ($ips as $ip) {
            $ip = htmlspecialchars($ip);
            // Silently skip this entry when it is empty or not a string (e.g. NULL)
            if ($ip === '') {
                continue;
            }

            $query = 'INSERT INTO client (ip,comment) VALUES (:ip,:comment)';
            $params = [
                ':ip'      => $ip,
                ':comment' => html_entity_decode($postData['comment'])
            ];
            $this->gravity->doQuery($query, $params);
            ++$added;
        }

        if ($added !== $total) {
            return Helper::returnJSONError('Not all clients have been added successfully');
        }

        return ['success' => true];
    }

    /**
     * @param $postData
     * @return bool[]
     * @throws \JsonException
     */
    public function deleteClient($postData)
    {
        $groups = json_decode($postData['id'], true, 512, JSON_THROW_ON_ERROR);
        $groups = implode(',', $groups);
        $tables = [
            'client_by_group' => 'group_id',
            'client'          => 'id'
        ]; // quote reserved word
        foreach ($tables as $table => $column) {
            $query = sprintf('DELETE FROM %s WHERE %s IN (%s)', $table, $column, $groups);
            $this->gravity->doQuery($query);
        }

        return ['success' => true];
    }

    public function editClient($postData)
    {
        $query = 'UPDATE client SET comment=:comment WHERE id = :id';
        // Update the comment
        $params = [
            ':comment' => html_entity_decode($postData['comment']),
            ':id'      => $postData['id']
        ];
        $this->gravity->doQuery($query, $params);


        // Update the groups
        $groupQuery = 'DELETE FROM client_by_group WHERE client_id = :id';
        $this->gravity->doQuery($groupQuery, [':id' => $postData['id']]);

        if (isset($postData['groups'])) {
            foreach ($postData['groups'] as $gid) {
                $insertGroupQuery = 'INSERT INTO client_by_group (client_id,group_id) VALUES(:id,:gid);';
                $params = [
                    ':id'  => $postData['id'],
                    ':gid' => $gid
                ];
                $this->gravity->doQuery($insertGroupQuery, $params);
            }
        }

        return ['success' => true];
    }
}
