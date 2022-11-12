<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class DNS extends Frontend
{
    public function __construct()
    {
        parent::__construct();
        $this->menuItems['Local DNS'] = 'active menu-open';
    }

    public function getDNSRecords(RequestInterface $request, ResponseInterface $response)
    {
        $this->menuItems['DNS Records'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/DNS/Records.twig', ['MenuItems' => $this->menuItems]);
    }

    public function getCNAMERecords(RequestInterface $request, ResponseInterface $response)
    {
        $this->menuItems['CNAME Records'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/DNS/CNAME.twig', ['MenuItems' => $this->menuItems]);
    }
}
