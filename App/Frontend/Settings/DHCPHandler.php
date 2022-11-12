<?php

namespace App\Frontend\Settings;

use App\Frontend\Settings;
use App\Helper\Config;
use App\Helper\Helper;
use App\PiHole;

class DHCPHandler extends Settings
{
    public static function handleAction($postData, &$success, &$error)
    {
        if (isset($postData['addstatic'])) {
            $mac = trim($postData['AddMAC']);
            $ip = trim($postData['AddIP']);
            $hostname = trim($postData['AddHostname']);

            self::addStaticDHCPLease($mac, $ip, $hostname, $success, $error);

            return;
        }

        if (isset($postData['removestatic'])) {
            $mac = strtoupper($postData['removestatic']);
            if (!filter_var($mac, FILTER_VALIDATE_MAC)) {
                $error .= sprintf('MAC address (%s) is invalid!<br>', htmlspecialchars($mac));
            }
            $mac = strtoupper($mac);

            if ($error === '') {
                PiHole::execute(sprintf('-a removestaticdhcp %s', $mac));
                $success .= sprintf('The static address with MAC address  (%s) has been removed', htmlspecialchars($mac));
            }

            return;
        }

        if (isset($postData['active'])) {
            // Validate from IP
            $from = $postData['from'];
            $to = $postData['to'];
            $router = $postData['router'];
            $domain = $postData['domain'];
            $leasetime = $postData['leasetime'];

            if (!Helper::validIP($from)) {
                $error .= sprintf('From IP (%s) is invalid!<br>', htmlspecialchars($from));
            }

            // Validate to IP
            if (!Helper::validIP($to)) {
                $error .= sprintf('To IP (%s) is invalid!<br>', htmlspecialchars($to));
            }

            // Validate router IP
            if (!Helper::validIP($router)) {
                $error .= sprintf('Router IP (%s) is invalid!<br>', htmlspecialchars($router));
            }

            // Validate Domain name
            if (!Helper::validDomain($domain)) {
                $error .= sprintf('Domain name %s is invalid!<br>', htmlspecialchars($domain));
            }

            // Validate Lease time length
            if (!is_numeric($leasetime) || (int)$leasetime < 0) {
                $error .= sprintf('Lease time %s is invalid!<br>', htmlspecialchars($leasetime));
            }

            $ipv6 = 'false';
            $type = '(IPv4)';
            if (isset($postData['useIPv6'])) {
                $ipv6 = 'true';
                $type = '(IPv4 + IPv6)';
            }

            $rapidcommit = isset($postData['DHCP_rapid_commit']);

            if ($error === '') {
                $cmd = sprintf('-a enabledhcp %s %s %s %s %s %s %s', $from, $to, $router, $leasetime, $domain, $ipv6, $rapidcommit ? 'true' : 'false');
                PiHole::execute($cmd);
                $success .= 'The DHCP server has been activated ' . htmlspecialchars($type);
            }
        } else {
            PiHole::execute('-a disabledhcp');
            $success = 'The DHCP server has been deactivated';
        }
    }

    private static function addStaticDHCPLease($mac, $ip, $hostname, &$success, &$error)
    {
        global $dhcp_static_leases;

        $mac = strtoupper($mac);
        if (!Helper::validIP($ip) && $ip !== '') {
            $error .= 'IP address (' . htmlspecialchars($ip) . ') is invalid!<br>';
        }

        if (!Helper::validDomain($hostname) && $hostname !== '') {
            $error .= 'Host name (' . htmlspecialchars($hostname) . ') is invalid!<br>';
        }

        if (empty($hostname) && empty($ip)) {
            $error .= 'You can not omit both the IP address and the host name!<br>';
        }

        if (empty($hostname)) {
            $hostname = 'nohost';
        }

        if (empty($ip)) {
            $ip = 'noip';
        }

        // Test if this lease is already included
        $dhcp_static_leases = (new Config())->getStaticLeases();

        foreach ($dhcp_static_leases as $lease) {
            if ($lease['hwaddr'] === $mac) {
                $error .= 'Static lease for MAC address (' . htmlspecialchars($mac) . ') already defined!<br>';
            }
            if ($ip !== 'noip' && $lease['IP'] === $ip) {
                $error .= 'Static lease for IP address (' . htmlspecialchars($ip) . ') already defined!<br>';
            }
            if ($lease['host'] === $hostname) {
                $error .= 'Static lease for hostname (' . htmlspecialchars($hostname) . ') already defined!<br>';
            }
        }

        $cmd = sprintf('-a addstaticdhcp %s %s %s', $mac, $ip, $hostname);

        $output = PiHole::execute($cmd);
        if (empty($output)) {
            $success .= 'A new static address has been added';
        }
    }
}
