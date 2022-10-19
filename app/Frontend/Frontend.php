<?php

namespace App\Frontend;

use App\API\PiHole;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;

class Frontend
{

    public function index(RequestInterface $request, ResponseInterface $response, $args)
    {
        $view = Twig::fromRequest($request);

        return $view->render($response, 'Layout.twig');
//        $response->getBody()->write($str);
//        return $response;
    }
}