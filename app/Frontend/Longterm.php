<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Longterm
{

    public function getGraph(RequestInterface $request, ResponseInterface $response, $args)
    {
        return Twig::fromRequest($request)->render($response, 'Pages/Longterm/Graph.twig');
    }

    public function getQueries(RequestInterface $request, ResponseInterface $response, $args)
    {
        return Twig::fromRequest($request)->render($response, 'Pages/Longterm/Queries.twig');

    }

    public function getList(RequestInterface $request, ResponseInterface $response, $args)
    {
        return Twig::fromRequest($request)->render($response, 'Pages/Longterm/List.twig');

    }
}