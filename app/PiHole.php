<?php

namespace App;

use App\DB\SQLiteDB;

/**
 *
 */
class PiHole
{
    /**
     * @var string
     */
    public const DEFAULT_FTLCONFFILE = '/etc/pihole/pihole-FTL.conf';

    /**
     * @var array
     */
    protected static $piholeFTLConfig = [];

    /**
     * @param $piholeFTLConfFile
     * @param $force
     * @return array|false
     */
    public static function getConfig($piholeFTLConfFile = self::DEFAULT_FTLCONFFILE, $force = false)
    {
        if (!$force && count(self::$piholeFTLConfig)) {
            return self::$piholeFTLConfig;
        }

        if (is_readable($piholeFTLConfFile)) {
            self::$piholeFTLConfig = parse_ini_file($piholeFTLConfFile);
        } else {
            self::$piholeFTLConfig = [];
        }

        return self::$piholeFTLConfig;
    }

    /**
     * @param $raw
     * @return array|false[]|string
     * @throws \Exception
     */
    public static function gravity_last_update($raw = false)
    {
        $db = new SQLiteDB('GRAVITYDB');
        $query = "SELECT value FROM info WHERE property=:property;";
        $result = $db->doQuery($query, [':property' => 'updated']);
        // Only fetch the first row. There shouldn't be any other anyway
        $date_file_created_unix = $result->fetchArray();
        if ($date_file_created_unix['value'] === false) {
            if ($raw) {
                return ['file_exists' => false];
            }

            return 'Gravity database not available';
        }
        // Destruct the SQLiteDB object
        $db = null;
        // Convert the UNIX timestamp to a Datetime and DateDiff
        $date_file_created = new \DateTime('@' . $date_file_created_unix['value']);
        $date_now = new \DateTime('now');
        $gravitydiff = date_diff($date_file_created, $date_now);
        if ($raw) {
            // Array output
            return [
                'file_exists' => true,
                'absolute'    => $date_file_created_unix['value'],
                'relative'    => [
                    'days'    => $gravitydiff->format('%a'),
                    'hours'   => $gravitydiff->format('%H'),
                    'minutes' => $gravitydiff->format('%I'),
                ],
            ];
        }

        switch ($days = $gravitydiff->d) {
            case $days > 1:
                $str = '%a days, ';
                break;
            case $days === 1:
                $str = 'one day, ';
                break;
            default:
                $str = '';
        }

        $str = sprintf('Adlists updated %s%%H:%%I (hh:mm) ago', $str);

        return $gravitydiff->format($str);
    }
}