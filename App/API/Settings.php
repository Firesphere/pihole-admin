<?php

namespace App\API;

use App\PiHole;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Settings extends APIBase
{
    public function getCacheInfo(RequestInterface $request, ResponseInterface $response)
    {
        $ftl = new CallAPI();
        $return = $ftl->doCall('cacheinfo');
        if (array_key_exists('FTLnotrunning', $return)) {
            return $this->returnAsJSON($request, $response, ['FTLnotrunning' => true]);
        }
        $cacheinfo = [];
        foreach ($return as $ret) {
            [$key, $value] = explode(': ', $ret);
            // Reply cannot contain non-ASCII characters
            $cacheinfo[$key] = (float)($value);
        }

        return $this->returnAsJSON($request, $response, ['cacheinfo' => $cacheinfo]);
    }
}
