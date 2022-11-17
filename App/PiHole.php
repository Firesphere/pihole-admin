<?php

namespace App;

use App\Helper\Config;
use RuntimeException;

/**
 *
 */
class PiHole
{
    /**
     * @return array|false
     */
    public static function getConfig()
    {
        return Config::get('pihole');
    }

    public static function execute($command)
    {
        exec('pihole -v', $output, $returnstatus);
        if ($returnstatus !== 0) {
            // pihole is not available
            return [
                'FTLnotrunning' => true,
                'message'       => 'Pi-hole is not available on this system.',
                'details'       => $output // Output the details
            ];
        }
        $output = null; // Reset the output
        $command = sprintf('sudo pihole %s', $command);
        exec($command, $output, $returnstatus);
        if ($returnstatus !== 0) {
            throw new RuntimeException("Executing {$command} failed.", E_USER_WARNING);
        }

        return $output;
    }
}
