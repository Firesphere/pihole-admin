<?php

namespace App\Middleware;

use App\Auth\Auth;
use App\Auth\Permission;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Routing\RouteContext;
use SlimSession\Helper;

class AuthMiddleware
{
    private static $public_routes = [
        '/login'
    ];

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (Helper::id()) {
            $route = RouteContext::fromRequest($request)->getRoute()->getName();
            $auth = new Auth();
            $response = new ResponseFactory();
            $response = $response->createResponse(401);
            $user = $auth->user();
            if (!$user) {
                return $response->withHeader('Location', '/login')->withStatus(302);
            }
            if ($route !== 'api' && Permission::check($route, $user) === false) {
                return $response->withHeader('Location', '/')->withStatus(302);
            }
            return $handler->handle($request, $handler);
        }


        throw new HttpUnauthorizedException($request, 'No session available.');
    }
}
