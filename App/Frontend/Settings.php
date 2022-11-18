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
use App\Helper\Config;
use App\Helper\Helper;
use App\Helper\QR\QRCode;
use App\Helper\QR\QRMath;
use App\PiHole;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Settings extends Frontend
{
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

    public static $themes = [
        'default-light'  => [
            'label' => 'Pi-hole default theme (light, default)',
            false,
            'key'   => 'default-light'
        ],
        'default-dark'   => [
            'label' => 'Pi-hole midnight theme (dark)',
            true,
            'key'   => 'default-dark'
        ],
        'default-darker' => [
            'label' => 'Pi-hole deep-midnight theme (dark)',
            true,
            'key'   => 'default-darker'
        ],
        // Option to have the theme go with the device dark mode setting, always set the background to black to avoid flashing
        'default-auto'   => [
            'label' => 'Pi-hole auto theme (light/dark)',
            true,
            'key'   => 'default-auto'
        ],
        'lcars'          => [
            'label' => 'Star Trek LCARS theme (dark)',
            true,
            'key'   => 'lcars'
        ],
    ];

    /**
     * @var CallAPI
     */
    private $api;

    public function __construct()
    {
        parent::__construct();
        /** @var \SlimSession\Helper $session */
        $this->api = new CallAPI();
        $this->config = new Config();

        $piholeConfig = Config::get('pihole');

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
            'API'             => $this->getAPISettings(),
            'Privacy'         => $this->getPrivacySettings(),
            'Teleporter'      => ['HasPHAR' => extension_loaded('Phar')],
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
        $environment = $view->getEnvironment();
        $this->renderPartials($activeTab, $environment);

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
                break;
                // Set query logging
            case 'Logging':
                LoggingHandler::handleAction($postData, $this->session, $success, $error);
                break;
                // Set domains to be excluded from being shown in Top Domains (or Ads) and Top Clients
            case 'API':
                APIHandler::handleAction($postData, $success, $error);
                break;
                // Config Web UI
            case 'webUI':
                WebUIHandler::handleAction($postData, $success, $error);
                break;
                // Power off the system
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
                DHCPHandler::handleAction($postData, $success, $error);
                break;
                // set Privacy level
            case 'privacyLevel':
                PrivacyHandler::handleAction($postData, $success, $error);
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
                break;
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
        $piholeConf = Config::get('pihole');

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
        $piholeConf = Config::get('pihole');

        $presetServers = $this->config->getDNSServerList();
        $activeServers = $this->getActiveDNSServers($presetServers);
        $ftlConf = Config::get('ftl');
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

    protected function getAPISettings()
    {
        $config = Config::get('pihole');
        $activeTheme = $config['WEBTHEME'] ?? '';
        $activeTheme = isset(static::$themes[$activeTheme]) ? $activeTheme : 'default-auto';

        return [
            'ExcludedDomains' => isset($config['API_EXCLUDE_DOMAINS']) ? explode(',', $config['API_EXCLUDE_DOMAINS']) : [],
            'ExcludedClients' => isset($config['API_EXCLUDE_CLIENTS']) ? explode(',', $config['API_EXCLUDE_CLIENTS']) : [],
            'QueryLog'        => $config['API_QUERY_LOG_SHOW'] ?? 'all',
            'APIToken'        => $this->session->get('token'),
            'Themes'          => static::$themes,
            'ActiveTheme'     => $activeTheme,
            'Boxed'           => isset($config['WEBUIBOXEDLAYOUT']) && $config['WEBUIBOXEDLAYOUT'] === 'boxed'
        ];
    }

    protected function getPrivacySettings()
    {
        $config = Config::get('pihole');
        $privacyLevel = (int)($config['PRIVACYLEVEL'] ?? 0);

        return [
            'PrivacyLevels' => [
                0 => [
                    'Label'    => 'Show everything and record everything',
                    'Selected' => $privacyLevel === 0,
                    'Note'     => 'Gives maximum amount of statistics',
                ],
                1 => [
                    'Selected' => $privacyLevel === 1,
                    'Label'    => 'Hide domains: Display and store all domains as "hidden"',
                    'Note'     => 'This disables the Top Permitted Domains and Top Blocked Domains tables on the dashboard'
                ],
                2 => [
                    'Selected' => $privacyLevel === 2,
                    'Label'    => 'Hide domains and clients: Display and store all domains as "hidden" and all clients as "0.0.0.0"',
                    'Note'     => 'This disables all tables on the dashboard'
                ],
                3 => [
                    'Selected' => $privacyLevel === 3,
                    'Label'    => 'Anonymous mode: This disables basically everything except the live anonymous statistics',
                    'Note'     => 'No history is saved at all to the database, and nothing is shown in the query log. Also, there are no top item lists.'
                ]
            ],
            'PrivacyLevel'  => $privacyLevel,
            'QueryLogging'  => $config['QUERY_LOGGING'] ?? true,
        ];
    }

    private function getActiveDNSServers($presetServers)
    {
        $servers = Config::get('pihole');
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

    /**
     * @param mixed $activeTab
     * @param Environment $environment
     * @return array|mixed
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function renderPartials(mixed $activeTab, Environment $environment)
    {
        foreach ($this->menuItems['Tabs'] as $key => &$value) {
            if ($value['Slug'] === $activeTab) {
                $value['Classes'] = 'active in ';
                $value['Expanded'] = true;
            }
            if ($value['Slug'] === 'api') { // QR Token modal
                QRMath::init();
                $qrCode = QRCode::getMinimumQRCode($this->session->get('token'), QR_ERROR_CORRECT_LEVEL_Q);
                $qr = [
                    'APIQRCode' => $qrCode->printSVG(10),
                    'APIToken'  => $this->session->get('token')
                ];
                $rendered = $environment->render('Partials/Settings/APIToken.twig', $qr);
                $this->settings['API']['QRFrame'] = $rendered;
            }
            $template = sprintf('Partials/Settings/Tabs/%s.twig', $key);
            $value['Template'] = $environment->render($template, $this->settings[$key]);
        }
    }
}
