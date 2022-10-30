<?php

namespace App\Frontend\Modules;


class TotalQueries extends Module
{
    public $sort = 2;

    public function getTemplate(): string
    {
        return 'Dashboard/TotalQueries.twig';
    }
}