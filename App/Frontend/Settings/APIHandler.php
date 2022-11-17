<?php

namespace App\Frontend\Settings;

use App\Frontend\Settings;
use App\Helper\Helper;
use App\PiHole;

class APIHandler extends Settings
{
    public static function handleAction($postData, &$success, &$error)
    {
        // Explode the contents of the textareas into PHP arrays
        // \n (Unix) and \r\n (Win) will be considered as newline
        // array_filter( ... ) will remove any empty lines
        $domains = array_filter(preg_split('/\r\n|[\r\n]/', $postData['domains']));
        $clients = array_filter(preg_split('/\r\n|[\r\n]/', $postData['clients']));

        $domainlist = '';
        $first = true;
        foreach ($domains as $domain) {
            if (!Helper::validDomainWildcard($domain) || Helper::validIP($domain)) {
                $error .= 'Top Domains/Ads entry ' . htmlspecialchars($domain) . ' is invalid (use only domains)!<br>';
            }
            if (!$first) {
                $domainlist .= ',';
            } else {
                $first = false;
            }
            $domainlist .= $domain;
        }

        $clientlist = '';
        $first = true;
        foreach ($clients as $client) {
            if (!Helper::validDomainWildcard($client) && !Helper::validIP($client)) {
                $error .= 'Top Clients entry ' . htmlspecialchars($client) . ' is invalid (use only host names and IP addresses)!<br>';
            }
            if (!$first) {
                $clientlist .= ',';
            } else {
                $first = false;
            }
            $clientlist .= $client;
        }

        // Set Top Lists options
        if (!strlen((string)$error)) {
            // All entries are okay
            PiHole::execute('-a setexcludedomains ' . $domainlist);
            PiHole::execute('-a setexcludeclients ' . $clientlist);
            $success .= 'The API settings have been updated<br>';
        } else {
            $error .= 'The settings have been reset to their previous values';
        }

        // Set query log options
        if (isset($postData['querylog-permitted'], $postData['querylog-blocked'])) {
            PiHole::execute('-a setquerylog all');
            $success .= 'All entries will be shown in Query Log';
        } elseif (isset($postData['querylog-permitted'])) {
            PiHole::execute('-a setquerylog permittedonly');
            $success .= 'Only permitted will be shown in Query Log';
        } elseif (isset($postData['querylog-blocked'])) {
            PiHole::execute('-a setquerylog blockedonly');
            $success .= 'Only blocked entries will be shown in Query Log';
        } else {
            PiHole::execute('-a setquerylog nothing');
            $success .= 'No entries will be shown in Query Log';
        }
    }
}
