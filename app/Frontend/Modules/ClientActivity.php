<?php

namespace App\Frontend\Modules;


use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ClientActivity extends Module implements ModuleInterface
{
    public $sort = 3;

    public function getTemplate(): string
    {
        return 'ClientActivity.twig';
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function renderTemplate($twigEnvironment): string
    {
        $path = sprintf('%s/%s', $this->getBaseFolder('Dashboard'), $this->getTemplate());
        return $twigEnvironment->render($path);
    }
}