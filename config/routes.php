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
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app) {
    $app->any('/login', [Frontend\Login::class, 'index']);
    $app->group('/api/', function (RouteCollectorProxy $group) {
        $group->get('status', [PiholeDB::class, 'status']);
        $group->get('summary', [FTL::class, 'summary']);
        $group->get('summaryRaw', [FTL::class, 'summary']);
        $group->get('enable', [FTL::class, 'startstop']);
        $group->get('disable[/{time}]', [FTL::class, 'startstop']);
        $group->get('maxlogage', [FTL::class, 'getMaxlogage']);
        $group->get('overTimeData', [FTL::class, 'overTimeData']);
        $group->get('getQueryTypes', [FTL::class, 'getQueryTypes']);
        $group->get('upstream', [FTL::class, 'getUpstreams']);
        $group->get('log', [FTL::class, 'tailLog']);
        $group->get('version', [PiHole::class, 'getVersion']);
        $group->get('getAllQueries', [Queries::class, 'getAll']);
        $group->get('getMinTimestamp', [PiholeDB::class, 'getMinTimestamp']);
        $group->get('getGraphData', [PiholeDB::class, 'getGraphData']);
        $group->get('getQueryLog', [PiholeDB::class, 'getQueryLogs']);
        $group->get('topClients', [PiholeDB::class, 'getTopClients']);
        $group->get('topDomains', [PiholeDB::class, 'getTopDomains']);
        $group->get('topAds', [PiholeDB::class, 'getTopAds']);
        $group->get('network', [PiholeDB::class, 'getNetwork']);
        $group->post('network', [PiholeDB::class, 'deleteNetwork']);
        $group->post('groups', [GroupPostHandler::class, 'postHandler']);

        // Custom DNS features
        $group->group('customdns/', function (RouteCollectorProxy $dnsGroup) {
            $dnsGroup->post('add', [DNSControl::class, 'addRecord']);
            $dnsGroup->post('delete', [DNSControl::class, 'deleteRecord']);
            $dnsGroup->get('get', [DNSControl::class, 'getExistingRecords']);
            $dnsGroup->get('getjson', [DNSControl::class, 'getAsJSON']);
            $dnsGroup->get('deleteAll/{type}', [DNSControl::class, 'deleteAll']); //??
        });

        $group->post('messages', [PiholeDB::class, 'deleteMessages']);
        $group->get('messages', [PiholeDB::class, 'getMessages']);

        $group->group('gravity', function (RouteCollectorProxy $gravityGroup) {
            $gravityGroup->get('/update', [Gravity::class, 'updateGravity']);
            $gravityGroup->get('/search', [Gravity::class, 'searchGravity']);
        });
        $group->get('debug', [PiHole::class, 'debug']);
        $group->group('settings', function (RouteCollectorProxy $settingsGroup) {
            $settingsGroup->get('/getCacheInfo', [Settings::class, 'getCacheInfo']);
        });
    });
    $app->get('/', [Frontend\Dashboard::class, 'index']);
    $app->get('/queries', [Frontend\Queries::class, 'index']);
    $app->group('/longterm', function (RouteCollectorProxy $group) {
        $group->get('/graph', [Frontend\Longterm::class, 'getGraph']);
        $group->get('/queries', [Frontend\Longterm::class, 'getQueries']);
        $group->get('/lists', [Frontend\Longterm::class, 'getList']);
    });
    $app->group('/groups', function (RouteCollectorProxy $group) {
        $group->get('', [Frontend\Group::class, 'index']);
        $group->get('/clients', [Frontend\Group::class, 'getClients']);
        $group->get('/domains', [Frontend\Group::class, 'getDomains']);
        $group->get('/adlists', [Frontend\Group::class, 'getList']);
    });
    $app->group('/dns', function (RouteCollectorProxy $group) {
        $group->get('/dns', [Frontend\DNS::class, 'getDNSRecords']);
        $group->get('/cname', [Frontend\DNS::class, 'getCNAMERecords']);
    });
    $app->group('/tools', function (RouteCollectorProxy $group) {
        $group->get('/messages', [Frontend\Tools::class, 'getMessages']);
        $group->get('/gravity', [Frontend\Tools::class, 'gravity']);
        $group->get('/search', [Frontend\Tools::class, 'getAdlistSearch']);
        $group->get('/auditlog', [Frontend\Tools::class, 'getAuditLog']);
        $group->get('/taillog', [Frontend\Tools::class, 'getTailLog']);
        $group->get('/debug', [Frontend\Tools::class, 'debug']);
        $group->get('/network', [Frontend\Tools::class, 'getNetwork']);
    });

    $app->get('/settings', [Frontend\Settings::class, 'index']);
    $app->post('/settings', [Frontend\Settings::class, 'handlePost']);
};
