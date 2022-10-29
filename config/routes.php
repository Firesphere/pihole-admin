<?php


use App\API\DNSControl;
use App\API\FTL;
use App\API\PiHole;
use App\API\PiholeDB;
use App\API\Queries;
use App\Frontend\Dashboard;
use App\Frontend\Group;
use App\Frontend\Longterm;
use App\Frontend\Queries as FrontendQueries;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app) {
    $app->group('/api/', function (RouteCollectorProxy $group) {
        $group->get('summary', [FTL::class, 'summary']);
        $group->get('summaryRaw', [FTL::class, 'summary']);
        $group->get('enable', [FTL::class, 'startstop']);
        $group->get('disable[/{time}]', [FTL::class, 'startstop']);
        $group->get('maxlogage', [FTL::class, 'getMaxlogage']);
        $group->get('overTimeData', [FTL::class, 'overTimeData']);
        $group->get('getQueryTypes', [FTL::class, 'getQueryTypes']);
        $group->get('upstream', [FTL::class, 'getUpstreams']);
        $group->get('version', [PiHole::class, 'getVersion']);
        $group->get('getAllQueries', [Queries::class, 'getAll']);
        $group->get('getMinTimestamp', [PiholeDB::class, 'getMinTimestamp']);
        $group->get('getGraphData', [PiholeDB::class, 'getGraphData']);
        $group->get('getQueryLog', [PiholeDB::class, 'getQueryLogs']);
        $group->get('topClients', [PiholeDB::class, 'getTopClients']);
        $group->get('topDomains', [PiholeDB::class, 'getTopDomains']);
        $group->get('topAds', [PiholeDB::class, 'getTopAds']);
        $group->post('groups', [Gro])
        // Custom DNS features
        $group->group('customdns/', function (RouteCollectorProxy $dnsGroup) {
            $dnsGroup->post('add', [PiHole::class, 'addRecord']);
            $dnsGroup->post('delete', [DNSControl::class, 'deleteRecord']);
            $dnsGroup->get('get', [DNSControl::class, 'getAllAsJSON']);
            $dnsGroup->get('deleteAll/{type}', [DNSControl::class, 'deleteAll']);
        });
    });
    $app->get('/', [Dashboard::class, 'index']);
    $app->get('/queries', [FrontendQueries::class, 'index']);
    $app->group('/longterm', function (RouteCollectorProxy $group) {
        $group->get('/graph', [Longterm::class, 'getGraph']);
        $group->get('/queries', [Longterm::class, 'getQueries']);
        $group->get('/lists', [Longterm::class, 'getList']);
    });
    $app->group('/groups', function (RouteCollectorProxy $group) {
        $group->get('', [Group::class, 'index']);
        $group->get('/clients',[Group::class, 'getClients']);
        $group->get('/domains',[Group::class, 'getDomains']);
        $group->get('/adlists', [Group::class, 'getLists']);
    });
};