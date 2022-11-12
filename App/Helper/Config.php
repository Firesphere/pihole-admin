<?php

namespace App\Helper;

class Config
{
    /**
     * @var []
     */
    protected $data;
    protected $default;

    public function __construct()
    {
        $this->data = require __DIR__ . '/../../config/settings.php';
    }

    public function get($key, $default = null)
    {
        $this->default = $default;

        $segments = explode('.', $key);
        $data = $this->data;

        foreach ($segments as $segment) {
            if (isset($data[$segment])) {
                $data = $data[$segment];
            } else {
                $data = $this->default;
                break;
            }
        }

        return $data;
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
        $handle = @fopen($this->data['dns']['SERVERS_CONF'], 'rb');
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
        $dhcp_leases = [];
        $dhcpleases = @fopen($this->data['dns']['DYNAMIC_LEASES_CONF'], 'rb');
        if (!is_resource($dhcpleases)) {
            return [];
        }

        while ($dhcplease = fgets($dhcpleases)) {
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

                $dhcp_leases[] = [
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

        return $dhcp_leases;
    }

    public function getStaticLeases()
    {
        $dhcp_static_leases = [];
        $dnsConf = $this->get('dns');

        if (!file_exists($dnsConf['STATIC_LEASES_CONF']) || !is_readable($dnsConf['STATIC_LEASES_CONF'])) {
            return false;
        }

        $dhcpstatic = @fopen($dnsConf['STATIC_LEASES_CONF'], 'rb');
        if (!is_resource($dhcpstatic)) {
            return false;
        }

        while (!feof($dhcpstatic)) {
            // Remove any possibly existing variable with this name
            $mac = '';
            $one = '';
            $two = '';
            sscanf(trim(fgets($dhcpstatic)), 'dhcp-host=%[^,],%[^,],%[^,]', $mac, $one, $two);
            if ($mac !== '' && filter_var($mac, FILTER_VALIDATE_MAC)) {
                if (Helper::validIP($one) && $two === '') {
                    // dhcp-host=mac,IP - no HOST
                    $dhcp_static_leases[] = ['hwaddr' => $mac, 'IP' => $one, 'host' => ''];
                } elseif (strlen($two) == 0) {
                    // dhcp-host=mac,hostname - no IP
                    $dhcp_static_leases[] = ['hwaddr' => $mac, 'IP' => '', 'host' => $one];
                } else {
                    // dhcp-host=mac,IP,hostname
                    $dhcp_static_leases[] = ['hwaddr' => $mac, 'IP' => $one, 'host' => $two];
                }
            } elseif (Helper::validIP($one) && Helper::validDomain($mac)) {
                // dhcp-host=hostname,IP - no MAC
                $dhcp_static_leases[] = ['hwaddr' => '', 'IP' => $one, 'host' => $mac];
            }
        }

        return $dhcp_static_leases;
    }
}
