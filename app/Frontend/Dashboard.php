<?php

namespace App\Frontend;



use App\Frontend\Modules\Module;
use App\Helper\Helper;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Dashboard extends Frontend
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $modules = (new Module())->getModules();
        $twig = Twig::fromRequest($request);
        $this->menuItems['Dashboard'] = 'active';
        $this->menuItems['includes'] = [];
        $env = $twig->getEnvironment();
        /** @var Module $subclass */
        foreach ($modules as $module) {
            $path = $module->getBaseFolder() . $module->getTemplate();
            $this->menuItems['includes'][$module->sort] = $env->render($path);
        }


        return $twig->render($response, 'Pages/Index.twig', $this->menuItems);
    }
}
