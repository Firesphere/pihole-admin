<?php


use App\API\DNSControl;
use App\API\FTL;
use App\API\GroupPostHandler;
use App\API\PiHole;
use App\API\PiholeDB;
use App\API\Queries;
use App\Frontend;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app) {
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
        $group->get('version', [PiHole::class, 'getVersion']);
        $group->get('getAllQueries', [Queries::class, 'getAll']);
        $group->get('getMinTimestamp', [PiholeDB::class, 'getMinTimestamp']);
        $group->get('getGraphData', [PiholeDB::class, 'getGraphData']);
        $group->get('getQueryLog', [PiholeDB::class, 'getQueryLogs']);
        $group->get('topClients', [PiholeDB::class, 'getTopClients']);
        $group->get('topDomains', [PiholeDB::class, 'getTopDomains']);
        $group->get('topAds', [PiholeDB::class, 'getTopAds']);
        $group->post('groups', [GroupPostHandler::class, 'postHandler']);
        // Custom DNS features
        $group->group('customdns/', function (RouteCollectorProxy $dnsGroup) {
            $dnsGroup->post('add', [DNSControl::class, 'addRecord']);
            $dnsGroup->post('delete', [DNSControl::class, 'deleteRecord']);
            $dnsGroup->get('get', [DNSControl::class, 'getExistingRecords']);
            $dnsGroup->get('getjson', [DNSControl::class, 'getAsJSON']);
            $dnsGroup->get('deleteAll/{type}', [DNSControl::class, 'deleteAll']); //??
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
};
