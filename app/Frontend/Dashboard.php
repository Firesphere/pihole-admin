<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Dashboard
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        return Twig::fromRequest($request)->render($response, 'Pages/Index.twig');
    }
}