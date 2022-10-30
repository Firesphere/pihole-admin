<?php

namespace App;

use RuntimeException;

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

    public static function execute($command)
    {
        exec('pihole -v', $output, $returnstatus);
        if ($returnstatus !== 0) {
            // pihole is not available
            return 'Did not restart Pi-hole, as it is not available on this system.';
        }
        $command = sprintf('sudo pihole %s', $command);
        exec($command, $output, $returnstatus);
        if ($returnstatus !== 0) {
            throw new RuntimeException("Executing {$command} failed.", E_USER_WARNING);
        }

        return $output;
    }
}
