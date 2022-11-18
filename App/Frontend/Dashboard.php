<?php

namespace App\Frontend;

use App\Frontend\Modules\Module;
use App\Frontend\Modules\ModuleInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;
use SlimSession\Helper;

class Dashboard extends Frontend
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $session = new Helper();
        $modules = (new Module())->getModules();
        $twig = Twig::fromRequest($request);
        $this->menuItems['Dashboard'] = 'active';
        $this->menuItems['Includes'] = [];
        $env = $twig->getEnvironment();
        /** @var ModuleInterface $module */
        foreach ($modules as $module) {
            $this->menuItems['includes'][$module->sort] = $module->renderTemplate($env);
        }


        return $twig->render($response, 'Pages/Index.twig', ['MenuItems' => $this->menuItems]);
    }
}
