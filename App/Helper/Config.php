<?php

namespace App\Helper;

class Config
{
    /**
     * @var []
     */
    protected $data;
    protected $default;

    public function __construct()
    {
        $this->data = require __DIR__ . '/../../config/settings.php';
    }

    public function get($key, $default = null)
    {
        $this->default = $default;

        $segments = explode('.', $key);
        $data = $this->data;

        foreach ($segments as $segment) {
            if (isset($data[$segment])) {
                $data = $data[$segment];
            } else {
                $data = $this->default;
                break;
            }
        }

        return $data;
    }
}
