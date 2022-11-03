<?php

use DI\Container;
use Slim\Factory\AppFactory;
use SlimSession\Helper;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();

// Register globally to app
$container->set('session', function () {
    return new Helper();
});
AppFactory::setContainer($container);
$app = AppFactory::create();

//$app->setBasePath('/pihole-admin');
// Twig global vars setup
$twig = require __DIR__ . '/twig.php';

// Register middleware
(require __DIR__ . '/middleware.php')($app, $twig);

require __DIR__ . '/modules.php';
require __DIR__ . '/settings.php';
// Register routes
(require __DIR__ . '/routes.php')($app);

// Get versions
return $app;
