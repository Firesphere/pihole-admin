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
        $this->menuItems['DNS'] = 'active menu-open';
    }

    public function getDNSRecords(RequestInterface $request, ResponseInterface $response)
    {
        $this->menuItems['DNSRecords'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/DNS/Records.twig', $this->menuItems);

    }

    public function getCNAMERecords(RequestInterface $request, ResponseInterface $response)
    {
        $this->menuItems['CNAMERecords'] = 'active';

        return Twig::fromRequest($request)->render($response, 'Pages/DNS/CNAME.twig', $this->menuItems);

    }
}