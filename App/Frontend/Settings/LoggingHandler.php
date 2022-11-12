<?php

namespace App\Frontend\Settings;

use App\Frontend\Settings;
use App\PiHole;

class LoggingHandler extends Settings
{
    public static function handleAction($postData, $session, &$success, &$error)
    {
        if ($postData['action'] === 'Disable') {
            PiHole::execute('-l off');
            $success = 'Logging has been disabled and logs have been flushed';
        } elseif ($postData['action'] === 'Disable-noflush') {
            PiHole::execute('-l off noflush');
            $success = 'Logging has been disabled, your logs have <strong>not</strong> been flushed';
        } else {
            PiHole::execute('-l on');
            $success = 'Logging has been enabled';
        }

        $session->set('SETTINGS_SUCCESS', $success);
        $session->set('SETTINGS_ERROR', false);
    }
}
