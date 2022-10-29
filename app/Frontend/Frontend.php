<?php

namespace App\Frontend;

class Frontend
{
    /**
     * @var array|false
     */
    protected $setupVars = [];

    protected $menuItems = [];

    public function __construct()
    {
        $this->setupVars = parse_ini_file(__DIR__ . '/../../setupVars.ini');
    }

}