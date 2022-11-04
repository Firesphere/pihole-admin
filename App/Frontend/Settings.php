<?php

namespace App\Frontend;

use App\API\CallAPI;
use App\API\FTLConnect;
use App\Helper\Helper;
use App\PiHole;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Settings extends Frontend
{
    /**
     * @var \SlimSession\Helper
     */
    private $session;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        /** @var \SlimSession\Helper $session */
        $this->session = $container->get('session');

        $this->menuItems['Tabs'] = [
            'System'     => [
                'Title'    => 'System',
                'Slug'     => 'sysadmin',
                'Active'   => 'active',
                'Expanded' => 'true'
            ],
            'DNS'        => [
                'Title'    => 'DNS',
                'Slug'     => 'dns',
                'Active'   => '',
                'Expanded' => 'false'
            ],
            'DHCP'       => [
                'Title'    => 'DHCP',
                'Slug'     => 'piholedhcp',
                'Active'   => '',
                'Expanded' => 'false'
            ],
            'API'        => [
                'Title'    => 'API/Web interface',
                'Slug'     => 'api',
                'Active'   => '',
                'Expanded' => 'false'
            ],
            'Privacy'    => [
                'Title'    => 'Privacy',
                'Slug'     => 'privacy',
                'Active'   => '',
                'Expanded' => 'false'
            ],
            'Teleporter' => [
                'Title'    => 'Teleporter',
                'Slug'     => 'teleporter',
                'Active'   => '',
                'Expanded' => 'false'
            ],
        ];
    }

    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $piholeConfig = $this->config->get('pihole');
        $api = new CallAPI();
        $IPv4txt = $this->getIPv4txt($api);
        $FTLPid = Helper::pidOf('pihole-FTL');
        $view = Twig::fromRequest($request);
        $this->menuItems['Settings'] = 'active';
        $variables = [
            'Success'         => $this->session->get('SETTINGS_SUCCESS'),
            'Error'           => $this->session->get('SETTINGS_ERROR'),
            'Config'          => $piholeConfig,
            'MenuItems'       => $this->menuItems,
            'PiHoleInterface' => $piholeConfig['PIHOLE_INTERFACE'] ?? 'unknown',
            'System'          => [
                'FTLPId'     => $FTLPid ?? 'FTL Not running',
                'FTLVersion' => $FTLPid ? exec('/usr/bin/pihole-FTL version') : '',
                'FTLStarted' => FTLConnect::getFTLData($FTLPid, 'lstart'),
                'FTLUser'    => FTLConnect::getFTLData($FTLPid, 'euser'),
                'FTLGroup'   => FTLConnect::getFTLData($FTLPid, 'egroup'),
                'FTLCPU'     => FTLConnect::getFTLData($FTLPid, '%cpu'),
                'FTLMEM'     => FTLConnect::getFTLData($FTLPid, '%mem'),
                'FTLRSS'     => Helper::formatByteUnits(1e3 * (float)FTLConnect::getFTLData($FTLPid, 'rss')),
            ],
            'IPv4'            => $IPv4txt,
        ];

        $this->session->delete('SETTINGS_SUCCESS');
        $this->session->delete('SETTINGS_ERROR');

        return $view->render($response, 'Pages/Settings.twig', $variables);
    }

    /**
     * @param CallAPI $api
     * @return mixed|string
     */
    public function getIPv4txt(CallAPI $api): mixed
    {
        $gatewayIP = ['ip' => '-1'];
        $gateway = $api->doCall('gateway');
        if (!isset($gateway['FTLnotrunning'])) {
            $gateway = explode(' ', $gateway[0]);
            $gatewayIP = array_combine(['ip', 'iface'], $gateway);
        }
        $IPv4txt = $gatewayIP['ip'];
        if (in_array($gatewayIP['ip'], ['0.0.0.0', '-1'])) {
            $IPv4txt = 'unknown';
        }

        return $IPv4txt;
    }

    public function handlePost(RequestInterface $request, ResponseInterface $response)
    {
        $postData = $request->getParsedBody();
        $DNSserverslist = $this->config->getDNSServerList();
        $types = [
            'v4_1',
            'v4_2',
            'v6_1',
            'v6_2'
        ];
        $error = '';
        $success = '';
        switch ($postData['field']) {
            // Set DNS server
            case 'DNS':
                $DNSservers = [];
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
                    if (strlen($domain) > 0 && !Helper::validDomain($domain)) {
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

                break;
                // Set query logging
            case 'Logging':
                if ($postData['action'] === 'Disable') {
                    PiHole::execute('-l off');
                    $success = 'Logging has been disabled and logs have been flushed';
                } elseif ($postData['action'] === 'Disable-noflush') {
                    PiHole::execute('-l off noflush');
                    $success = 'Logging has been disabled, your logs have <strong>not</strong> been flushed';
                } else {
                    PiHole::execute('-l on');
                    $success = 'Logging has been enabled';
                }

                $this->session->set('SETTINGS_SUCCESS', $success);
                $this->session->set('SETTINGS_ERROR', false);

                break;
                // Set domains to be excluded from being shown in Top Domains (or Ads) and Top Clients
            case 'API':
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
                if (!strlen($error)) {
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

                break;

            case 'webUI':
                if (isset($postData['boxedlayout'])) {
                    PiHole::execute('-a layout boxed');
                } else {
                    PiHole::execute('-a layout traditional');
                }
                if (isset($postData['webtheme'])) {
                    global $available_themes;
                    if (array_key_exists($postData['webtheme'], $available_themes)) {
                        exec('sudo pihole -a theme ' . $postData['webtheme']);
                    }
                }
                $success .= 'The webUI settings have been updated';

                break;

            case 'poweroff':
                PiHole::execute('-a poweroff');
                $success = 'The system will poweroff in 5 seconds...';

                break;

            case 'reboot':
                PiHole::execute('-a reboot');
                $success = 'The system will reboot in 5 seconds...';

                break;

            case 'restartdns':
                PiHole::execute('-a restartdns');
                $success = 'The DNS server has been restarted';

                break;

            case 'flushlogs':
                PiHole::execute('-f');
                $success = 'The Pi-hole log file has been flushed';

                break;

            case 'DHCP':
                if (isset($postData['addstatic'])) {
                    $mac = trim($postData['AddMAC']);
                    $ip = trim($postData['AddIP']);
                    $hostname = trim($postData['AddHostname']);

                    addStaticDHCPLease($mac, $ip, $hostname);

                    break;
                }

                if (isset($postData['removestatic'])) {
                    $mac = $postData['removestatic'];
                    if (!validMAC($mac)) {
                        $error .= 'MAC address (' . htmlspecialchars($mac) . ') is invalid!<br>';
                    }
                    $mac = strtoupper($mac);

                    if ($error === '') {
                        PiHole::execute('-a removestaticdhcp ' . $mac);
                        $success .= 'The static address with MAC address ' . htmlspecialchars($mac) . ' has been removed';
                    }

                    break;
                }

                if (isset($postData['active'])) {
                    // Validate from IP
                    $from = $postData['from'];
                    if (!Helper::validIP($from)) {
                        $error .= 'From IP (' . htmlspecialchars($from) . ') is invalid!<br>';
                    }

                    // Validate to IP
                    $to = $postData['to'];
                    if (!Helper::validIP($to)) {
                        $error .= 'To IP (' . htmlspecialchars($to) . ') is invalid!<br>';
                    }

                    // Validate router IP
                    $router = $postData['router'];
                    if (!Helper::validIP($router)) {
                        $error .= 'Router IP (' . htmlspecialchars($router) . ') is invalid!<br>';
                    }

                    $domain = $postData['domain'];

                    // Validate Domain name
                    if (!Helper::validDomain($domain)) {
                        $error .= 'Domain name ' . htmlspecialchars($domain) . ' is invalid!<br>';
                    }

                    $leasetime = $postData['leasetime'];

                    // Validate Lease time length
                    if (!is_numeric($leasetime) || (int)$leasetime < 0) {
                        $error .= 'Lease time ' . htmlspecialchars($leasetime) . ' is invalid!<br>';
                    }

                    if (isset($postData['useIPv6'])) {
                        $ipv6 = 'true';
                        $type = '(IPv4 + IPv6)';
                    } else {
                        $ipv6 = 'false';
                        $type = '(IPv4)';
                    }

                    if (isset($postData['DHCP_rapid_commit'])) {
                        $rapidcommit = 'true';
                    } else {
                        $rapidcommit = 'false';
                    }

                    if ($error === '') {
                        PiHole::execute('-a enabledhcp ' . $from . ' ' . $to . ' ' . $router . ' ' . $leasetime . ' ' . $domain . ' ' . $ipv6 . ' ' . $rapidcommit);
                        $success .= 'The DHCP server has been activated ' . htmlspecialchars($type);
                    }
                } else {
                    PiHole::execute('-a disabledhcp');
                    $success = 'The DHCP server has been deactivated';
                }

                break;

            case 'privacyLevel':
                $level = intval($postData['privacylevel']);
                if ($level >= 0 && $level <= 4) {
                    // Check if privacylevel is already set
                    if (isset($piholeFTLConf['PRIVACYLEVEL'])) {
                        $privacylevel = intval($piholeFTLConf['PRIVACYLEVEL']);
                    } else {
                        $privacylevel = 0;
                    }

                    // Store privacy level
                    PiHole::execute('-a privacylevel ' . $level);

                    if ($privacylevel > $level) {
                        PiHole::execute('-a restartdns');
                        $success .= 'The privacy level has been decreased and the DNS resolver has been restarted';
                    } elseif ($privacylevel < $level) {
                        $success .= 'The privacy level has been increased';
                    } else {
                        $success .= 'The privacy level has not been changed';
                    }
                } else {
                    $error .= 'Invalid privacy level (' . $level . ')!';
                }

                break;
                // Flush network table
            case 'flusharp':
                $output = PiHole::execute('arpflush quiet');
                $error = '';
                if (is_array($output)) {
                    $error = implode('<br>', $output);
                }
                if (strlen($error) == 0) {
                    $success .= 'The network table has been flushed';
                }

                break;

            default:
                // Option not found
                $error = 'Invalid option';
        }

        $this->session->set('SETTINGS_SUCCESS', $success);
        $this->session->set('SETTINGS_ERROR', $error);

        return $response->withHeader('Location', '/settings')
            ->withStatus(302);
    }
}
