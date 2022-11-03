<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Settings extends Frontend
{
    public function __construct()
    {
        parent::__construct();
        $this->menuItems['Tabs'] = [
            'System'     => [
                'Title'    => 'System',
                'Slug'     => 'sysadmin',
                'Active'   => 'active',
                'Expanded' => 'true'
            ],
            'DNS'        => [
                'Title'    => 'DNS',
                'Slug'     => 'dns',
                'Active'   => '',
                'Expanded' => 'false'
            ],
            'DHCP'       => [
                'Title'    => 'DHCP',
                'Slug'     => 'piholedhcp',
                'Active'   => '',
                'Expanded' => 'false'
            ],
            'API'        => [
                'Title'    => 'API/Web interface',
                'Slug'     => 'api',
                'Active'   => '',
                'Expanded' => 'false'
            ],
            'Privacy'    => [
                'Title'    => 'Privacy',
                'Slug'     => 'privacy',
                'Active'   => '',
                'Expanded' => 'false'
            ],
            'Teleporter' => [
                'Title'    => 'Teleporter',
                'Slug'     => 'teleporter',
                'Active'   => '',
                'Expanded' => 'false'
            ],
        ];
    }

    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['Settings'] = 'active';

        return $view->render($response, 'Pages/Settings.twig', $this->menuItems);
    }
}
