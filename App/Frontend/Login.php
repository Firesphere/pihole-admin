<?php

namespace App\Frontend;

use App\Auth\Auth;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Login extends Frontend
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        return Twig::fromRequest($request)->render($response, 'Login.twig');
    }

    public function login(RequestInterface $request, ResponseInterface $response)
    {
        $postData = $request->getParsedBody();
        $auth = new Auth();
        if ($auth->login($postData['username'], $postData['pw']) !== false) {
            return $response->withStatus(302, 'Success')->withHeader('Location', '/');
        }

        $response->withStatus(401);
        $body = [
            'error' => true
        ];

        return Twig::fromRequest($request)->render($response, 'Login.twig', $body);
    }
}
