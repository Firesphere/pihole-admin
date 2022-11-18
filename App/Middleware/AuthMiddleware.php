<?php

namespace App\Middleware;

use App\Auth\Auth;
use http\Exception\RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use SlimSession\Helper;

class AuthMiddleware
{
    private static $public_routes = [
        '/login'
    ];

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (Helper::id()) {
            $auth = new Auth();
            if (!$auth->check()) {
                $response = new ResponseFactory();
                $response = $response->createResponse(401);

                return $response->withHeader('Location', '/login')->withStatus(302);
            }
            return $handler->handle($request, $handler);
        }

        throw new RuntimeException('No session available.');
    }
}
