<?php

namespace App;

class SysInfo
{
    public static function getAll()
    {
        return [
            self::getMemoryUse(),
            self::getLoad(),
            self::getCPUCount(),
            self::getTemperature()
        ];
    }

    public static function getMemoryUse()
    {
        $file = fopen('/proc/meminfo', 'r');
        $meminfo = [];
        while ($line = fgets($file)) {
            $expl = explode(':', $line);
            if (count($expl) == 2) {
                $kb = rtrim(trim($expl[1]), ' kB');
                $meminfo[rtrim($expl[0], ':')] = $kb;
            }
        }
        $memused = $meminfo['MemTotal'] - $meminfo['MemFree'] - $meminfo['Buffers'] - $meminfo['Cached'];

        return $memused / $meminfo['MemTotal'];
    }

    public static function getLoad()
    {
        return sys_getloadavg();
    }

    public static function getCPUCount()
    {
        $cpuinfo = file_get_contents('/proc/cpuinfo');
        preg_match_all('/^processor/m', $cpuinfo, $matches);

        return count($matches[0]);
    }

    public static function getTemperature()
    {
        if (file_exists('/sys/class/thermal/thermal_zone0/temp')) {
            $output = rtrim(file_get_contents('/sys/class/thermal/thermal_zone0/temp'));
        } elseif (file_exists('/sys/class/hwmon/hwmon0/temp1_input')) {
            $output = rtrim(file_get_contents('/sys/class/hwmon/hwmon0/temp1_input'));
        } else {
            $output = '';
        }

        // Test if we succeeded in getting the temperature
        if (is_numeric($output)) {
            // $output could be either 4-5 digits or 2-3, and we only divide by 1000 if it's 4-5
            // ex. 39007 vs 39
            $celsius = (int)$output;

            // If celsius is greater than 1 degree and is in the 4-5 digit format
            if ($celsius > 1000) {
                // Use multiplication to get around the division-by-zero error
                $celsius *= 1e-3;
            }

        } else {
            // Nothing can be colder than -273.15 degree Celsius (= 0 Kelvin)
            // This is the minimum temperature possible (AKA absolute zero)
            $celsius = false;
        }

        return $celsius;
    }
}