<?php

namespace App\Frontend\Modules;


use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Overview extends Module implements ModuleInterface
{
    public $sort = 1;

    public function getTemplate(): string
    {
        return 'Overview.twig';
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