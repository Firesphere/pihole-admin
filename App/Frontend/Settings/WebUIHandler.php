<?php

namespace App\Frontend\Settings;

use App\Frontend\Settings;
use App\PiHole;

class WebUIHandler extends Settings
{
    public static function handleAction($postData, &$success, &$error)
    {
        if (isset($postData['boxedlayout'])) {
            PiHole::execute('-a layout boxed');
        } else {
            PiHole::execute('-a layout traditional');
        }
        if (isset($postData['webtheme']) && array_key_exists($postData['webtheme'], Settings::$themes)) {
            PiHole::execute(sprintf('-a theme %s', $postData['webtheme']));
        }
        $success .= 'The webUI settings have been updated';
    }
}
