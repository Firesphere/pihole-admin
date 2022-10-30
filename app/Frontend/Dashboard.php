<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Dashboard extends Frontend
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $this->menuItems['Dashboard'] = 'active';
        return Twig::fromRequest($request)->render($response, 'Pages/Index.twig', $this->menuItems);
    }
}
