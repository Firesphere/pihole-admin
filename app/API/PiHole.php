<?php

namespace App\API;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PiHole
{
    private const VERSION_FILE = '/var/www/html/versions';
    protected static $urls = [
        'coreUrl'   => 'https://github.com/pi-hole/pi-hole/releases',
        'webUrl'    => 'https://github.com/pi-hole/AdminLTE/releases',
        'ftlUrl'    => 'https://github.com/pi-hole/FTL/releases',
        'dockerUrl' => 'https://github.com/pi-hole/docker-pi-hole/releases',
    ];
    /**
     * @var array|false
     */
    private $parsedVersions;

    /**
     * Check if the Versions file is readable.
     * If so, load it
     */
    public function __construct()
    {
        if (!is_readable(static::VERSION_FILE)) {
            throw new \InvalidArgumentException('Version file not found');
        }

        $this->parsedVersions = parse_ini_file(static::VERSION_FILE);
    }

    public function getVersion(ServerRequestInterface $request, ResponseInterface $response)
    {
        $parts = [
            'CORE'   => [
                'branch'  => 'master',
                'current' => 'N/A',
                'update'  => false
            ],
            'WEB'    => [
                'branch'  => 'master',
                'current' => 'N/A',
                'update'  => false
            ],
            'FTL'    => [
                'branch'  => 'master',
                'current' => 'N/A',
                'update'  => false
            ],
            'DOCKER' => [
                'branch'  => 'master',
                'current' => 'N/A',
                'update'  => false
            ]
        ];

        foreach ($parts as $key => &$part) {
            $part = $this->getVersionsFor($key);
            if ($part['current'] !== 'N/A') {
                $part['update'] = $this->isLatest($parts[$key]['current'], $parts[$key]['latest']);
            }
        }
        unset($part);

        $body = $response->getBody();
        $body->write(json_encode($parts));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param $part
     * @return array|false[]
     */
    protected function getVersionsFor($part)
    {
        $return = [
            'branch'  => $this->parsedVersions[$part . '_BRANCH'] ?? false,
            'current' => $this->parsedVersions[$part . '_VERSION'] ?? false,
            'latest'  => $this->parsedVersions['GITHUB_' . $part . '_VERSION'] ?? false
        ];
        if ($return['branch'] !== 'master' && $return['branch'] !== false) {
            $return['current'] = 'vDev';
            $return['commit'] = $this->parsedVersions[$part . '_VERSION'];
        }

        return $return;
    }

    protected function isLatest($current, $latest)
    {
        // This logic allows the local core version to be newer than the upstream version
        // The update indicator is only shown if the upstream version is NEWER
        if ($current !== 'vDev') {
            [$current, $commit] = explode('-', $current);

            return version_compare($current, $latest) < 0;
        }

        return false;
    }

    /**
     * @return array|false
     */
    public function getParsedVersions(): bool|array
    {
        return $this->parsedVersions;
    }
}