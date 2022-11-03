<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Login extends Frontend
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        return Twig::fromRequest($request)->render($response, 'Login.twig', $this->menuItems);
    }
}
