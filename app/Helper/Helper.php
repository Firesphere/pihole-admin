<?php

namespace App\Helper;

class Helper
{

    public static function returnJSONError($error, $params = [])
    {
        $response = ['success' => false, 'message' => $error];
        if (!empty($params['action'])) {
            $response['action'] = $params['action'];
        }
        return $response;
    }
}