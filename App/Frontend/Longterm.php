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
        $this->menuItems['Long-term Data'] = 'active menu-open';
    }

    public function getGraph(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->menuItems['Graphics'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/Longterm/Graph.twig', ['MenuItems' => $this->menuItems]);
    }

    public function getQueries(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->menuItems['Query Log'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/Longterm/Queries.twig', ['MenuItems' => $this->menuItems]);
    }

    public function getList(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->menuItems['Top Lists'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/Longterm/List.twig', ['MenuItems' => $this->menuItems]);
    }
}
