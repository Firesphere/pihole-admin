<?php

namespace App\DB;

use App\PiHole;
use SQLite3;
use SQLite3Result;

class SQLiteDB
{
    /**
     * @var SQLite3
     */
    private $db;

    /**
     * Locations of the different DBs
     * @var string[]
     */
    private static $dbs = [
        'GRAVITYDB' => '/var/www/pihole-admin/gravity.db',
        'FTLDB'     => '/var/www/pihole-admin/pihole-FTL.db',
        'USERDB'    => '/var/www/pihole-admin/users.db'
];

    public function __construct($type, $mode = SQLITE3_OPEN_READONLY)
    {
        $this->db = new SQLite3($this->getDBLocation($type), $mode);
    }

    public function __destruct()
    {
        $this->db->close();
    }

    private function getDBLocation(string $type)
    {

        // Get possible non-standard location of FTL's database, if found
        $FTLsettings = file_exists(PiHole::DEFAULT_FTLCONFFILE) ?
            parse_ini_file(PiHole::DEFAULT_FTLCONFFILE) :
            [];

        return $FTLsettings[$type] ?? static::$dbs[$type];
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
}