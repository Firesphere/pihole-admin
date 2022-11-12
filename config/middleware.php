<?php

use App\Helper\Config;
use Slim\App;
use Slim\Middleware\Session;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return static function (App $app, Twig $twig, Config $config) {
    // Parse json, form data and xml
    $app->addBodyParsingMiddleware();

    // Add routing
    $app->addRoutingMiddleware();
    // Add Twig-View Middleware
    $app->add(TwigMiddleware::create($app, $twig));
    // Add session
    $app->add(
        new Session([
            'name'        => 'PHPSESSID',
            'autorefresh' => true,
            'lifetime'    => '24 hour',
            'httponly'    => false,
        ])
    );
    /**
     * Add Error Handling Middleware
     *
     * @param bool $displayErrorDetails -> Should be set to false in production
     * @param bool $logErrors -> Parameter is passed to the default ErrorHandler
     * @param bool $logErrorDetails -> Display error details in error log
     * Note: This middleware should be added last. It will not handle any exceptions/errors
     * for middleware added after it.
     */    $production = $config->get('production');
    // Add error handling middleware.
    $displayErrorDetails = !$production;
    $logErrors = $production;
    $logErrorDetails = $production;
    $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);
};
