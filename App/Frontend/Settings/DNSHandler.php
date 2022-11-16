<?php

namespace App\Frontend\Settings;

use App\Frontend\Settings;
use App\Helper\Config;
use App\Helper\Helper;
use App\PiHole;

class DNSHandler extends Settings
{
    /**
     * @param $postData
     * @param Config $config
     * @param $success
     * @param $error
     * @return void
     */
    public static function handleAction($postData, $config, &$success, &$error)
    {
        $DNSservers = [];
        $DNSserverslist = $config->getDNSServerList();
        $types = [
            'v4_1',
            'v4_2',
            'v6_1',
            'v6_2'
        ];

        // Add selected predefined servers to list
        foreach ($DNSserverslist as $key => $value) {
            foreach ($types as $type) {
                // Skip if this IP type does not
                // exist (e.g. IPv4-only or only
                // one IPv6 address upstream
                // server)
                if (!array_key_exists($type, $value)) {
                    continue;
                }

                // If server exists and is set
                // (POST), we add it to the
                // array of DNS servers
                $server = str_replace('.', '_', $value[$type]);
                if (array_key_exists('DNSserver' . $server, $postData)) {
                    $DNSservers[] = $value[$type];
                }
            }
        }

        // Test custom server fields
        for ($i = 1; $i <= 4; ++$i) {
            if (array_key_exists('custom' . $i, $postData)) {
                [$ip, $port] = explode('#', $postData['custom' . $i . 'val'], 2);

                if (!Helper::validIP($ip)) {
                    $error = sprintf('IP (%s) is invalid!<br>', htmlspecialchars($ip));
                } else {
                    if (!is_numeric($port)) {
                        $error .= sprintf('Port (%s) is invalid!<br>', htmlspecialchars($port));
                    } else {
                        $DNSservers[] = sprintf('%s#%d', $ip, $port);
                    }
                }
            }
        }
        $DNSservercount = count($DNSservers);

        // Check if at least one DNS server has been added
        if ($DNSservercount < 1) {
            $error .= 'No valid DNS server has been selected.<br>';
        }

        $extra = [
            'domain-not-needed',
            'no-bogus-priv',
            'no-dnssec'
        ];
        // Check if domain-needed is requested
        if (isset($postData['DNSrequiresFQDN'])) {
            $extra[0] = 'domain-needed';
        }

        // Check if domain-needed is requested
        if (isset($postData['DNSbogusPriv'])) {
            $extra[1] = 'bogus-priv ';
        }

        // Check if DNSSEC is requested
        if (isset($postData['DNSSEC'])) {
            $extra[2] = 'dnssec';
        }

        // Check if rev-server is requested
        if (isset($postData['rev_server'])) {
            // Validate CIDR IP
            $cidr = trim($postData['rev_server_cidr']);
            if (!Helper::validCIDRIP($cidr)) {
                $error .= 'Conditional forwarding subnet ("' . htmlspecialchars($cidr) . '") is invalid!<br>' .
                    'This field requires CIDR notation for local subnets (e.g., 192.168.0.0/16).<br>';
            }

            // Validate target IP
            $target = trim($postData['rev_server_target']);
            if (!Helper::validIP($target)) {
                $error .= 'Conditional forwarding target IP ("' . htmlspecialchars($target) . '") is invalid!<br>';
            }

            // Validate conditional forwarding domain name (empty is okay)
            $domain = trim($postData['rev_server_domain']);
            if ((string)$domain !== '' && !Helper::validDomain($domain)) {
                $error .= 'Conditional forwarding domain name ("' . htmlspecialchars($domain) . '") is invalid!<br>';
            }

            if (!$error) {
                $extra[] = sprintf('rev-server %s %s %s', $cidr, $target, $domain);
            }
        }
        $validInterfaces = [
            'single',
            'bind',
            'all',
            'local'
        ];

        $DNSinterface = in_array($postData['DNSinterface'], $validInterfaces) ? $postData['DNSinterface'] : 'local';

        PiHole::execute('-a -i ' . $DNSinterface . ' -web');

        // Add rate-limiting settings
        if (isset($postData['rate_limit_count'], $postData['rate_limit_interval'])) {
            // Restart of FTL is delayed
            PiHole::execute(sprintf('-a ratelimit %d %d false', (int)$postData['rate_limit_count'], (int)$postData['rate_limit_interval']));
        }

        // If there has been no error we can save the new DNS server IPs
        if ($error === '') {
            $IPs = implode(',', $DNSservers);
            $extra = implode(' ', $extra);
            $cmd = sprintf('-a setdns %s %s', $IPs, $extra);
            $return = PiHole::execute($cmd);
            $success .= htmlspecialchars(end($return)) . '<br>';
            $success .= 'The DNS settings have been updated (using ' . $DNSservercount . ' DNS servers)';
        } else {
            $error .= 'The settings have been reset to their previous values';
        }
    }
}
