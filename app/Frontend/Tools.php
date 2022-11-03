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

    public function getAdlistSearch(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['AdlistSearch'] = 'active';

        return $view->render($response, 'Pages/Tools/AdlistSearch.twig', $this->menuItems);
    }

    public function getAuditLog(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['AuditLog'] = 'active';

        return $view->render($response, 'Pages/Tools/AuditLog.twig', $this->menuItems);
    }

    public function getTailLog(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $params = $request->getQueryParams();
        $this->menuItems['Log'] = 'Pihole.log';
        $this->menuItems['Logtype'] = '';
        $this->menuItems['Taillog'] = 'active';
        if (isset($params['FTL'])) {
            $this->menuItems['Log'] = 'FTL.log';
            $this->menuItems['Logtype'] = 'FTL';
            $this->menuItems['TailFTL'] = 'active';
            $this->menuItems['Taillog'] = '';
        }

        return $view->render($response, 'Pages/Tools/TailLog.twig', $this->menuItems);
    }

    public function debug(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['Debug'] = 'active';

        return $view->render($response, 'Pages/Tools/Debug.twig', $this->menuItems);
    }
}
