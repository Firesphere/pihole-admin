<?php

namespace App\Frontend;

use App\API\CallAPI;
use App\API\FTLConnect;
use App\Frontend\Settings\APIHandler;
use App\Frontend\Settings\DHCPHandler;
use App\Frontend\Settings\DNSHandler;
use App\Frontend\Settings\LoggingHandler;
use App\Frontend\Settings\PrivacyHandler;
use App\Frontend\Settings\WebUIHandler;
use App\Helper\Helper;
use App\PiHole;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Settings extends Frontend
{
    /**
     * @var \SlimSession\Helper
     */
    private $session;

    protected $settings = [];

    protected $menuItems = [
        'Settings' => 'active',
        'Tabs'     => [
            'System'     => [
                'Title' => 'System',
                'Slug'  => 'sysadmin',
            ],
            'DNS'        => [
                'Title' => 'DNS',
                'Slug'  => 'dns',
            ],
            'DHCP'       => [
                'Title' => 'DHCP',
                'Slug'  => 'piholedhcp',
            ],
            'API'        => [
                'Title' => 'API/Web interface',
                'Slug'  => 'api',
            ],
            'Privacy'    => [
                'Title' => 'Privacy',
                'Slug'  => 'privacy',
            ],
            'Teleporter' => [
                'Title' => 'Teleporter',
                'Slug'  => 'teleporter',
            ],
        ]
    ];

    /**
     * @var CallAPI
     */
    private $api;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        /** @var \SlimSession\Helper $session */
        $this->session = $container->get('session');
        $this->api = new CallAPI();

        $piholeConfig = $this->config->get('pihole');

        $IPv4txt = $this->getIPv4txt();
        $FTLPid = Helper::pidOf('pihole-FTL');

        $this->settings = [
            'Success'         => $this->session->get('SETTINGS_SUCCESS'),
            'Error'           => $this->session->get('SETTINGS_ERROR'),
            'Config'          => $piholeConfig,
            'PiHoleInterface' => $piholeConfig['PIHOLE_INTERFACE'] ?? 'unknown',
            'System'          => $this->getSystemSettings($FTLPid),
            'DHCP'            => $this->getDHCPSettings(),
            'DNS'             => $this->getDNSSettings(),
            'IPv4'            => $IPv4txt,
        ];
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $get = $request->getQueryParams();
        $activeTab = $get['tab'] ?? 'sysadmin';
        $view = Twig::fromRequest($request);

        $this->session->delete('SETTINGS_SUCCESS');
        $this->session->delete('SETTINGS_ERROR');
        foreach ($this->menuItems['Tabs'] as $key => &$value) {
            if ($value['Slug'] === $activeTab) {
                $value['Classes'] = 'active in ';
                $value['Expanded'] = true;
            }
            $template = sprintf('Partials/Settings/Tabs/%s.twig', $key);
            $value['Template'] = $view->getEnvironment()->render($template, $this->settings);
        }
        unset($value);
        $this->settings['MenuItems'] = $this->menuItems;


        return $view->render($response, 'Pages/Settings.twig', $this->settings);
    }

    /**
     * @return mixed|string
     */
    public function getIPv4txt(): mixed
    {
        $gateway = $this->api->doCall('gateway');
        $gatewayIP = ['ip' => '-1'];
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
        $error = '';
        $success = '';
        switch ($postData['field']) {
            // Set DNS server
            case 'DNS':
                DNSHandler::handleAction($postData, $this->config, $success, $error);
                // Set query logging
                // no break
            case 'Logging':
                LoggingHandler::handleAction($postData, $this->session, $success, $error);
                // Set domains to be excluded from being shown in Top Domains (or Ads) and Top Clients
                // no break
            case 'API':
                APIHandler::handleAction($postData, $success, $error);
                // Config Web UI
                // no break
            case 'webUI':
                WebUIHandler::handleAction($postData, $success, $error);
                // Power off the system
                // no break
            case 'poweroff':
                PiHole::execute('-a poweroff');
                $success = 'The system will poweroff in 5 seconds...';
                break;
                // Reboot the system
            case 'reboot':
                PiHole::execute('-a reboot');
                $success = 'The system will reboot in 5 seconds...';
                break;
                // restart Pihole-FTL
            case 'restartdns':
                PiHole::execute('-a restartdns');
                $success = 'The DNS server has been restarted';
                break;
                // Flush the logs
            case 'flushlogs':
                PiHole::execute('-f');
                $success = 'The Pi-hole log file has been flushed';
                break;
                // Set DHCP
            case 'DHCP':
                DHCPHandler::handleAction($postData, $this->config, $success, $error);
                break;
                // set Privacy level
            case 'privacyLevel':
                PrivacyHandler::handleAction($postData, $this->config, $success, $error);
                break;
                // Flush network table
            case 'flusharp':
                $output = PiHole::execute('arpflush quiet');
                if (is_array($output)) {
                    $error = implode('<br>', $output);
                }
                if ($error === '') {
                    $success = 'The network table has been flushed';
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

    /**
     * @param int|bool|null $FTLPid
     * @return array
     */
    public function getSystemSettings($FTLPid): array
    {
        return [
            'PId'     => $FTLPid ?? 'FTL Not running',
            'Version' => $FTLPid ? exec('/usr/bin/pihole-FTL version') : '',
            'Started' => FTLConnect::getFTLData($FTLPid, 'lstart'),
            'User'    => FTLConnect::getFTLData($FTLPid, 'euser'),
            'Group'   => FTLConnect::getFTLData($FTLPid, 'egroup'),
            'CPU'     => FTLConnect::getFTLData($FTLPid, '%cpu'),
            'MEM'     => FTLConnect::getFTLData($FTLPid, '%mem'),
            'RSS'     => Helper::formatByteUnits(1e3 * (float)FTLConnect::getFTLData($FTLPid, 'rss')),
        ];
    }

    protected function getDHCPSettings()
    {
        $piholeConf = $this->config->get('pihole');

        return [
            'Active'        => (isset($piholeConf['DHCP_ACTIVE']) && $piholeConf['DHCP_ACTIVE'] === 1),
            'Start'         => $piholeConf['DHCP_START'] ?? '',
            'End'           => $piholeConf['DHCP_END'] ?? '',
            'Router'        => $piholeConf['DHCP_ROUTER'] ?? '',
            'Domain'        => $piholeConf['PIHOLE_DOMAIN'] ?? '',
            'Lease'         => $piholeConf['DHCP_LEASETIME'] ?? 24,
            'RapidCommit'   => $piholeConf['DHCP_rapid_commit'] ?? false,
            'StaticLeases'  => $this->config->getStaticLeases(),
            'DynamicLeases' => $this->config->getDynamicLeases(),
            'IPv6'          => $piholeConf['DHCP_IPv6'] ?? false,
        ];
    }

    protected function getDNSSettings()
    {
        $piholeConf = $this->config->get('pihole');

        $presetServers = $this->config->getDNSServerList();
        $activeServers = $this->getActiveDNSServers($presetServers);
        $ftlConf = $this->config->get('ftl');
        $ratelimit = 1000;
        $ratelimitinterval = 60;
        if (isset($ftlConf['RATE_LIMIT'])) {
            [$ratelimit, $ratelimitinterval] = explode('/', $ftlConf['RATE_LIMIT']);
        }

        return [
            'Servers'           => $presetServers,
            'ActiveServers'     => $activeServers[0],
            'CustomServers'     => $activeServers[1],
            'DNSMasq'           => $this->getDNSMasq($piholeConf),
            'Interface'         => $piholeConf['PIHOLE_INTERFACE'],
            'RequireFQDN'       => $piholeConf['DNS_FQDN_REQUIRED'] ?? false,
            'BogusPriv'         => $piholeConf['DNS_BOGUS_PRIV'] ?? false,
            'DNSSec'            => $piholeConf['DNSSEC'] ?? false,
            'Ratelimit'         => $ratelimit,
            'Ratelimitinterval' => $ratelimitinterval,
            'RevServer'         => isset($piholeConf['REV_SERVER']),
            'RevServerCIDR'     => $piholeConf['REV_SERVER_CIDR'] ?? '',
            'RevServerTarget'   => $piholeConf['REV_SERVER_TARGET'] ?? '',
            'RevServerDomain'   => $piholeConf['REV_SERVER_DOMAIN'] ?? ''
        ];
    }

    private function getActiveDNSServers($presetServers)
    {
        $servers = $this->config->get('pihole');
        $preset = [];
        $custom = [];

        foreach ($servers as $key => $value) {
            if (str_starts_with($key, 'PIHOLE_DNS_')) {
                if ($this->isPreset($value, $presetServers)) {
                    $preset[] = $value;
                } else {
                    if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $custom[4][] = $value;
                    } else {
                        $custom[6][] = $value;
                    }
                }
            }
        }

        return [$preset, $custom];
    }

    private function isPreset($value, $presetServers)
    {
        foreach ($presetServers as $provider => $server) {
            if (in_array($value, array_values($server))) {
                return true;
            }
        }

        return false;
    }

    private function getDNSMasq($conf)
    {
        $return = 'single';
        $options = [
            'single',
            'bind',
            'all',
        ];
        if (in_array($conf['DNSMASQ_LISTENING'], $options)) {
            $return = $conf['DNSMASQ_LISTENING'];
        } elseif (isset($conf['DNSMASQ_LISTENING'])) {
            $return = 'local';
        }

        return $return;
    }
}
