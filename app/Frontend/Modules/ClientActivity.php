<?php

namespace App\Frontend\Modules;


class ClientActivity extends Module
{
    public $sort = 3;

    public function getTemplate(): string
    {
        return 'Dashboard/ClientActivity.twig';
    }
}