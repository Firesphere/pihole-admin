<?php

namespace App\Helper;

class Config
{
    /**
     * @var []
     */
    public static $data;
    protected static $default;

    public function __construct()
    {
        if (!self::$data) {
            self::$data = require __DIR__ . '/../../config/settings.php';
        }
    }

    public function getDNSServerList()
    {
        $list = [];
        $types = [
            1 => 'v4_1',
            2 => 'v4_2',
            3 => 'v6_1',
            4 => 'v6_2'
        ];
        $handle = @fopen(self::$data['dns']['SERVERS_CONF'], 'rb');
        if ($handle) {
            while ($line = fgets($handle)) {
                $line = explode(';', rtrim($line));
                $name = $line[0];
                $values = [];
                foreach ($types as $i => $type) {
                    if (isset($line[$i]) && Helper::validIP($line[$i])) {
                        $values[$type] = $line[$i];
                    }
                }
                $list[$name] = $values;
            }
            fclose($handle);
        }

        return $list;
    }

    public function getDynamicLeases()
    {
        // Read leases file
        $dhcpLeases = [];
        $dhcpFile = @fopen(self::$data['dns']['DYNAMIC_LEASES_CONF'], 'rb');
        if (!is_resource($dhcpFile)) {
            return [];
        }

        while ($dhcplease = fgets($dhcpFile)) {
            [$time, $hwaddr, $ip, $host, $clid] = explode(' ', trim($dhcplease));
            if ($clid) {
                $time = (int)$time;
                if ($time === 0) {
                    $time = 'Infinite';
                } elseif ($time <= 315360000) { // 10 years in seconds
                    $time = Helper::secondsToTime($time);
                } else { // Assume time stamp
                    $time = Helper::secondsToTime($time - time());
                }

                $type = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 4 : 6;


                $host = htmlentities($host);

                if ($clid === '*') {
                    $clid = '<i>unknown</i>';
                }

                $order = bin2hex(inet_pton($ip));

                $dhcpLeases[] = [
                    'time'   => $time,
                    'hwaddr' => strtoupper($hwaddr),
                    'IP'     => $ip,
                    'host'   => $host,
                    'clid'   => $clid,
                    'type'   => $type,
                    'order'  => $order
                ];
            }
        }

        return $dhcpLeases;
    }

    public function getStaticLeases()
    {
        $staticDHCPLeases = [];
        $dnsConf = self::get('dns');

        if (!file_exists($dnsConf['STATIC_LEASES_CONF']) || !is_readable($dnsConf['STATIC_LEASES_CONF'])) {
            return false;
        }

        $staticDHCPFile = @fopen($dnsConf['STATIC_LEASES_CONF'], 'rb');
        if (!is_resource($staticDHCPFile)) {
            return false;
        }

        while (!feof($staticDHCPFile)) {
            // Remove any possibly existing variable with this name
            $mac = '';
            $one = '';
            $two = '';
            sscanf(trim(fgets($staticDHCPFile)), 'dhcp-host=%[^,],%[^,],%[^,]', $mac, $one, $two);
            if ($mac !== '' && filter_var($mac, FILTER_VALIDATE_MAC)) {
                if ($two === '' && Helper::validIP($one)) {
                    // dhcp-host=mac,IP - no HOST
                    $staticDHCPLeases[] = ['hwaddr' => $mac, 'IP' => $one, 'host' => ''];
                } elseif ((string)$two === "") {
                    // dhcp-host=mac,hostname - no IP
                    $staticDHCPLeases[] = ['hwaddr' => $mac, 'IP' => '', 'host' => $one];
                } else {
                    // dhcp-host=mac,IP,hostname
                    $staticDHCPLeases[] = ['hwaddr' => $mac, 'IP' => $one, 'host' => $two];
                }
            } elseif (Helper::validIP($one) && Helper::validDomain($mac)) {
                // dhcp-host=hostname,IP - no MAC
                $staticDHCPLeases[] = ['hwaddr' => '', 'IP' => $one, 'host' => $mac];
            }
        }

        return $staticDHCPLeases;
    }

    /**
     * Get a config setting
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    public static function get($key, $default = null)
    {
        $data = self::$data;

        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (isset($data[$segment])) {
                $data = $data[$segment];
            } else {
                $data = $default;
                break;
            }
        }

        return $data;
    }
}
