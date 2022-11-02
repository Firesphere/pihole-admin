<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Tools extends Frontend
{
    public function __construct()
    {
        parent::__construct();
        $this->menuItems['Tools'] = 'active menu-open';
    }

    public function getMessages(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['Message'] = 'active';

        return $view->render($response, 'Pages/Tools/Messages.twig', $this->menuItems);
    }

    public function gravity(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['Gravity'] = 'active';

        return $view->render($response, 'Pages/Tools/Gravity.twig', $this->menuItems);
    }
}
