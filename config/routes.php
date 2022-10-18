<?php


use App\API\DNSControl;
use App\API\FTL;
use App\API\PiHole;
use App\Frontend\Index;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app) {
    $app->get('/', [Index::class, 'index']);
    $app->group('/api/', function (RouteCollectorProxy $group) {
        $group->get('summary', [FTL::class, 'summary']);
        $group->get('summaryRaw', [FTL::class, 'summary']);
        $group->get('enable', [FTL::class, 'startstop']);
        $group->get('disable[/{time}]', [FTL::class, 'startstop']);
        $group->get('getMaxlogage', [FTL::class, 'getMaxlogage']);
        $group->get('version', [PiHole::class, 'getVersion']);
        // Custom DNS features
        $group->group('customdns/', function (RouteCollectorProxy $dnsGroup) {
            $dnsGroup->post('add', [PiHole::class, 'addRecord']);
            $dnsGroup->post('delete', [DNSControl::class, 'deleteRecord']);
            $dnsGroup->get('get', [DNSControl::class, 'getAllAsJSON']);
            $dnsGroup->get('deleteAll/{type}', [DNSControl::class, 'deleteAll']);
        });
    });
};