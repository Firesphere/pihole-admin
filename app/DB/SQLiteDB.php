<?php

namespace App\DB;

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
     * Locations of the different DBs
     * @var string[]
     */
    private static $dbs = [
        'GRAVITYDB' => '/var/www/html/gravity.db',
        'FTLDB'     => '/var/www/html/pihole-FTL.db',
        'USERDB'    => '/var/www/html/users.db',
    ];
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
        // Get possible non-standard location of FTL's database, if found
        $FTLsettings = file_exists(PiHole::DEFAULT_FTLCONFFILE) ?
            parse_ini_file(PiHole::DEFAULT_FTLCONFFILE) :
            [];

        return $FTLsettings[$type] ?? static::$dbs[$type];
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
