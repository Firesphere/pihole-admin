<?php

use App\Helper\Config;
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\Session;
use SlimSession\Helper;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();

$config = new Config();

// Register globally to app
$container->set('session', function () {
    return new \SlimSession\Helper();
});
\Slim\Factory\AppFactory::setContainer($container);
AppFactory::setContainer($container);

$app = AppFactory::create();

//$app->setBasePath('/pihole-admin');
// Twig global vars setup
$twig = require __DIR__ . '/twig.php';

// Register middleware
(require __DIR__ . '/middleware.php')($app, $twig);

require __DIR__ . '/modules.php';
// Register routes
(require __DIR__ . '/routes.php')($app);

require __DIR__ . '/qr.php';
// Get versions

$auth = new \App\Auth\Auth($container);

return $app;
