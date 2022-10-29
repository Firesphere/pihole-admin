<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Group extends Frontend
{

    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $this->menuItems['Groups'] = 'active';
        return Twig::fromRequest($request)->render($response, 'Pages/Groups/Groups.twig', $this->menuItems);
    }
}