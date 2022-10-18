<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Index
{

    public function index(RequestInterface $request, ResponseInterface $response, $args)
    {
        $view = Twig::fromRequest($request);

        $str = $view->fetchFromString('<h1>Hello {{ name }}</h1>', [
            'name' => 'Simon'
        ]);
        $response->getBody()->write($str);
        return $response;
    }
}