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
        $handle = @fopen('/etc/pihole/dns-servers.conf', 'rb');
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
}
