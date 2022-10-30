<?php

namespace App\Frontend\Modules;


class Overview extends Module
{
    public $sort = 1;

    public function getTemplate(): string
    {
        return 'Dashboard/Overview.twig';
    }
}