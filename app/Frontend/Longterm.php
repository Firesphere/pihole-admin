<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Longterm extends Frontend
{
    public function __construct()
    {
        parent::__construct();
        $this->menuItems['Longterm'] = 'active menu-open';
    }

    public function getGraph(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->menuItems['LongtermGraph'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/Longterm/Graph.twig', $this->menuItems);
    }

    public function getQueries(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->menuItems['LongtermQueryLog'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/Longterm/Queries.twig', $this->menuItems);

    }

    public function getList(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->menuItems['LongtermList'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/Longterm/List.twig', $this->menuItems);
    }
}