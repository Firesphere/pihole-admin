<?php

namespace App\Frontend\Settings;

use App\Frontend\Settings;
use App\PiHole;

class PrivacyHandler extends Settings
{
    public static function handleAction($postData, $config, &$success, &$error)
    {
        $level = (int)$postData['privacylevel'];
        $change = -2;
        if ($level >= 0 && $level <= 4) {
            // Check if privacylevel is already set
            $privacylevel = (int)($config['Config']['PRIVACYLEVEL'] ?? 0);

            // Store privacy level
            PiHole::execute('-a privacylevel ' . $level);

            $change = $privacylevel <=> $level;
        }
        switch ($change) {
            case -1:
                $success .= 'The privacy level has been increased';
                break;
            case 0:
                $success .= 'The privacy level is unchanged';
                break;
            case 1:
                PiHole::execute('-a restartdns');
                $success .= 'The privacy level has been decreased and the DNS resolver has been restarted';
                break;
            default:
                $error .= sprintf('Invalid privacy level (%d)!', $level);
        }
    }
}
