<?php


use App\API\DNSControl;
use App\API\FTL;
use App\API\Gravity\Gravity;
use App\API\GroupPostHandler;
use App\API\PiHole;
use App\API\PiholeDB;
use App\API\Queries;
use App\API\Settings;
use App\Frontend;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $slimApp) {
    $slimApp->get('/login', [Frontend\Login::class, 'index'])->setName('login');
    $slimApp->post('/login', [Frontend\Login::class, 'login'])->setName('doLogin');
    $slimApp->get('logout', [Frontend\Login::class, 'logout']);
    $slimApp->group('/', function (RouteCollectorProxy $app) {
        $app->group('api/', function (RouteCollectorProxy $group) {
            $group->get('status', [PiholeDB::class, 'status'])->setName('api');
            $group->get('summary', [FTL::class, 'summary'])->setName('api');
            $group->get('summaryRaw', [FTL::class, 'summary'])->setName('api');
            $group->get('enable', [FTL::class, 'startstop'])->setName('api');
            $group->get('disable[/{time}]', [FTL::class, 'startstop'])->setName('api');
            $group->get('maxlogage', [FTL::class, 'getMaxlogage'])->setName('api');
            $group->get('overTimeData', [FTL::class, 'overTimeData'])->setName('api');
            $group->get('getQueryTypes', [FTL::class, 'getQueryTypes'])->setName('api');
            $group->get('upstream', [FTL::class, 'getUpstreams'])->setName('api');
            $group->get('log', [FTL::class, 'tailLog'])->setName('api');
            $group->get('version', [PiHole::class, 'getVersion'])->setName('api');
            $group->get('getAllQueries', [Queries::class, 'getAll'])->setName('api');
            $group->get('getMinTimestamp', [PiholeDB::class, 'getMinTimestamp'])->setName('api');
            $group->get('getGraphData', [PiholeDB::class, 'getGraphData'])->setName('api');
            $group->get('getQueryLog', [PiholeDB::class, 'getQueryLogs'])->setName('api');
            $group->get('topClients', [PiholeDB::class, 'getTopClients'])->setName('api');
            $group->get('topDomains', [PiholeDB::class, 'getTopDomains'])->setName('api');
            $group->get('topAds', [PiholeDB::class, 'getTopAds'])->setName('api');
            $group->get('network', [PiholeDB::class, 'getNetwork'])->setName('api');
            $group->post('network', [PiholeDB::class, 'deleteNetwork'])->setName('api');
            $group->post('groups', [GroupPostHandler::class, 'postHandler'])->setName('api');

            // Custom DNS features
            $group->group('customdns/', function (RouteCollectorProxy $dnsGroup) {
                $dnsGroup->post('add', [DNSControl::class, 'addRecord'])->setName('api');
                $dnsGroup->post('delete', [DNSControl::class, 'deleteRecord'])->setName('api');
                $dnsGroup->get('get', [DNSControl::class, 'getExistingRecords'])->setName('api');
                $dnsGroup->get('getjson', [DNSControl::class, 'getAsJSON'])->setName('api');
                $dnsGroup->get('deleteAll/{type}', [DNSControl::class, 'deleteAll'])->setName('api'); //??
            });

            $group->post('messages', [PiholeDB::class, 'deleteMessages'])->setName('api');
            $group->get('messages', [PiholeDB::class, 'getMessages'])->setName('api');

            $group->group('gravity', function (RouteCollectorProxy $gravityGroup) {
                $gravityGroup->get('/update', [Gravity::class, 'updateGravity'])->setName('api');
                $gravityGroup->get('/search', [Gravity::class, 'searchGravity'])->setName('api');
            });
            $group->get('debug', [PiHole::class, 'debug'])->setName('api');
            $group->group('settings', function (RouteCollectorProxy $settingsGroup) {
                $settingsGroup->get('/getCacheInfo', [Settings::class, 'getCacheInfo'])->setName('api');
            });
        });
        $app->get('', [Frontend\Dashboard::class, 'index'])->setName('dashboard');
        $app->get('queries', [Frontend\Queries::class, 'index'])->setName('queries');
        $app->group('longterm', function (RouteCollectorProxy $group) {
            $group->get('/graph', [Frontend\Longterm::class, 'getGraph'])->setName('longterm');
            $group->get('/queries', [Frontend\Longterm::class, 'getQueries'])->setName('longterm');
            $group->get('/lists', [Frontend\Longterm::class, 'getList'])->setName('longterm');
        });
        $app->group('groups', function (RouteCollectorProxy $group) {
            $group->get('', [Frontend\Group::class, 'index'])->setName('group');
            $group->get('/clients', [Frontend\Group::class, 'getClients'])->setName('group');
            $group->get('/domains', [Frontend\Group::class, 'getDomains'])->setName('group');
            $group->get('/adlists', [Frontend\Group::class, 'getList'])->setName('group');
        });
        $app->group('dns', function (RouteCollectorProxy $group) {
            $group->get('/dns', [Frontend\DNS::class, 'getDNSRecords'])->setName('dns');
            $group->get('/cname', [Frontend\DNS::class, 'getCNAMERecords'])->setName('dns');
        });
        $app->group('tools', function (RouteCollectorProxy $group) {
            $group->get('/messages', [Frontend\Tools::class, 'getMessages'])->setName('tools');
            $group->get('/gravity', [Frontend\Tools::class, 'gravity'])->setName('tools');
            $group->get('/search', [Frontend\Tools::class, 'getAdlistSearch'])->setName('tools');
            $group->get('/auditlog', [Frontend\Tools::class, 'getAuditLog'])->setName('tools');
            $group->get('/taillog', [Frontend\Tools::class, 'getTailLog'])->setName('tools');
            $group->get('/debug', [Frontend\Tools::class, 'debug'])->setName('tools');
            $group->get('/network', [Frontend\Tools::class, 'getNetwork'])->setName('tools');
        });

        $app->get('settings', [Frontend\Settings::class, 'index'])->setName('settings');
        $app->post('settings', [Frontend\Settings::class, 'handlePost'])->setName('settings');
        $app->post('teleporter', [Frontend\Settings\TeleporterHandler::class, 'teleport'])->setName('settings');
    })->add(new AuthMiddleware());
};
