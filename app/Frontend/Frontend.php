<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Frontend
{

    public function index(RequestInterface $request, ResponseInterface $response, $args)
    {
        $view = Twig::fromRequest($request);

        return $view->render($response, 'Layout.twig');
    }
}