<?php
error_reporting(E_ALL);

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$app = AppFactory::create();

$app->setBasePath('/pihole-admin');

/**
 * The routing middleware should be added before the ErrorMiddleware
 * Otherwise exceptions thrown from it will not be handled
 */
$app->addRoutingMiddleware();

$app->group('/api/', function (RouteCollectorProxy $group) {
    $group->get('summary', [App\API\FTL::class, 'summary']);
    $group->get('summaryRaw', [App\API\FTL::class, 'summary']);
    $group->get('enable', [App\API\FTL::class, 'startstop']);
    $group->get('disable[/{time}]', [App\API\FTL::class, 'startstop']);
    $group->get('getMaxlogage', [App\API\FTL::class, 'getMaxlogage']);
});
/**
 * Add Error Handling Middleware
 *
 * @param bool $displayErrorDetails -> Should be set to false in production
 * @param bool $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool $logErrorDetails -> Display error details in error log
 * which can be replaced by a callable of your choice.

 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */
$app->addErrorMiddleware(true, true, true);

$app->run();