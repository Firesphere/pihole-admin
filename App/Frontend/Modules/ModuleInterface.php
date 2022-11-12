<?php

namespace App\Frontend\Modules;

use Twig\Environment;

interface ModuleInterface
{
    /**
     * @return string
     */
    public function getTemplate(): string;

    /**
     * @param Environment $twigEnvironment
     * @return string
     */
    public function renderTemplate($twigEnvironment): string;
}
