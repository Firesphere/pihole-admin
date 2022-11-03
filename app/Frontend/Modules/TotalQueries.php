<?php

namespace App\Frontend\Modules;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TotalQueries extends Module implements ModuleInterface
{
    public $sort = 2;

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

    public function getTemplate(): string
    {
        return 'TotalQueries.twig';
    }
}
