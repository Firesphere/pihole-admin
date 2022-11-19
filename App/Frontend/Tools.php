<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Tools extends Frontend
{
    protected $name = 'tools';

    public function __construct()
    {
        parent::__construct();
        $this->menuItems['Tools'] = 'active menu-open';
    }

    public function getMessages(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $view->getEnvironment()->getGlobals();
        $this->menuItems['Pi-hole diagnosis'] = 'active';

        return $view->render($response, 'Pages/Tools/Messages.twig', ['MenuItems' => $this->menuItems]);
    }

    public function gravity(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['Update Gravity'] = 'active';

        return $view->render($response, 'Pages/Tools/Gravity.twig', ['MenuItems' => $this->menuItems]);
    }

    public function getAdlistSearch(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['Search Adlists'] = 'active';

        return $view->render($response, 'Pages/Tools/AdlistSearch.twig', ['MenuItems' => $this->menuItems]);
    }

    public function getAuditLog(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['Audit Log'] = 'active';

        return $view->render($response, 'Pages/Tools/AuditLog.twig', ['MenuItems' => $this->menuItems]);
    }

    public function getTailLog(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $params = $request->getQueryParams();
        $this->menuItems['Log'] = 'Pihole.log';
        $this->menuItems['Logtype'] = '';
        $this->menuItems['Tail pihole.log'] = 'active';
        if (isset($params['FTL'])) {
            $this->menuItems['Log'] = 'FTL.log';
            $this->menuItems['Logtype'] = 'FTL';
            $this->menuItems['Tail FTL.log'] = 'active';
            $this->menuItems['Taillog'] = '';
        }

        return $view->render($response, 'Pages/Tools/TailLog.twig', ['MenuItems' => $this->menuItems]);
    }

    public function debug(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['Generate debug log'] = 'active';

        return $view->render($response, 'Pages/Tools/Debug.twig', ['MenuItems' => $this->menuItems]);
    }

    public function getNetwork(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $this->menuItems['Network'] = 'active';

        return $view->render($response, 'Pages/Tools/Network.twig', ['MenuItems' => $this->menuItems]);
    }
}
