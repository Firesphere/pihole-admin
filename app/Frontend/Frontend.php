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
        $page = $view->getEnvironment()->render('Pages/Index.twig');

        return $view->render($response, 'Layout.twig', ['Page' => $page]);
    }
}