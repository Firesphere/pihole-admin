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

class Settings extends Frontend
{
    /**
     * @var \SlimSession\Helper
     */
    private $session;

    protected $settings = [];

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        /** @var \SlimSession\Helper $session */
        $this->session = $container->get('session');
        $this->menuItems['Settings'] = 'active';

        $this->menuItems['Tabs'] = [
            'System'     => [
                'Title' => 'System',
                'Slug'  => 'sysadmin',
            ],
            'DHCP'       => [
                'Title' => 'DHCP',
                'Slug'  => 'piholedhcp',
            ],
            'DNS'        => [
                'Title' => 'DNS',
                'Slug'  => 'dns',
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
        ];
        $piholeConfig = $this->config->get('pihole');
        $api = new CallAPI();
        $IPv4txt = $this->getIPv4txt($api);
        $FTLPid = Helper::pidOf('pihole-FTL');
        $this->settings = [
            'Success'         => $this->session->get('SETTINGS_SUCCESS'),
            'Error'           => $this->session->get('SETTINGS_ERROR'),
            'Config'          => $piholeConfig,
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
    }

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
     * @param CallAPI $api
     * @return mixed|string
     */
    public function getIPv4txt(CallAPI $api): mixed
    {
        $gateway = $api->doCall('gateway');
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
