<?php

namespace App\Frontend;

use App\Frontend\Modules\Module;
use App\Helper\Helper;
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

    public function getClients(RequestInterface $request, ResponseInterface $response)
    {
        $this->menuItems['Clients'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/Groups/Clients.twig', $this->menuItems);
    }

    public function getDomains(RequestInterface $request, ResponseInterface $response)
    {
        $this->menuItems['Domains'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/Groups/Domains.twig', $this->menuItems);
    }

    public function getList(RequestInterface $request, ResponseInterface $response)
    {
        $this->menuItems['List'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/Groups/List.twig', $this->menuItems);
    }
}
