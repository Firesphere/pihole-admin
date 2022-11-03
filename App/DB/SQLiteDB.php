<?php

namespace App\DB;

use App\Helper\Config;
use App\PiHole;
use SQLite3;
use SQLite3Result;

class SQLiteDB
{
    public const LISTTYPE_WHITELIST = 0;
    public const LISTTYPE_BLACKLIST = 1;
    public const LISTTYPE_REGEX_WHITELIST = 2;
    public const LISTTYPE_REGEX_BLACKLIST = 3;

    /**
     * @var SQLite3
     */
    private $db;

    public function __construct($type, $mode = SQLITE3_OPEN_READONLY)
    {
        $this->db = new SQLite3($this->getDBLocation($type), $mode);
    }

    private function getDBLocation(string $type)
    {
        $conf = new Config();
        $config = $conf->get('db');
        $ftlConfig = $conf->get('ftl');

        return $ftlConfig[$type . 'DB'] ?? $config[$type];
    }

    public function __destruct()
    {
        $this->db->close();
    }

    /**
     * Execute a query, as prepared statement if parameters are passed.
     * Otherwise, it's executed and returned directly
     *
     * @param string $query
     * @param array $params
     * @return false|SQLite3Result
     */
    public function doQuery(string $query, array $params = [])
    {
        if (count($params)) {
            $prepared = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $prepared->bindValue($key, $value);
            }

            return $prepared->execute();
        }

        return $this->db->query($query);
    }

    /**
     * @return SQLite3
     */
    public function getDb(): SQLite3
    {
        return $this->db;
    }
}
