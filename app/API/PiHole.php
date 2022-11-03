<?php

namespace App\API;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PiHole
{
    private const VERSION_FILE = __DIR__ . '/../../versions';
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


    protected static $ANSIcolors = [
        '[1;91m' => '<span class="log-red">',
        '[1;32m' => '<span class="log-green">',
        '[1;33m' => '<span class="log-yellow">',
        '[1;34m' => '<span class="log-blue">',
        '[1;35m' => '<span class="log-purple">',
        '[1;36m' => '<span class="log-cyan">',

        '[90m' => '<span class="log-gray">',
        '[91m' => '<span class="log-red">',
        '[32m' => '<span class="log-green">',
        '[33m' => '<span class="log-yellow">',
        '[94m' => '<span class="log-blue">',
        '[95m' => '<span class="log-purple">',
        '[96m' => '<span class="log-cyan">',

        '[1m' => '<span class="text-bold">',
        '[4m' => '<span class="text-underline">',

        '[0m' => '</span>',
    ];


    /**
     * Check if the Versions file is readable.
     * If so, load it
     */
    public function __construct()
    {
        if (!is_readable(static::VERSION_FILE)) {
            throw new InvalidArgumentException('Version file not found');
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


    public function debug(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        ob_end_flush();
        ini_set('output_buffering', '0');
        ob_implicit_flush(true);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        // Execute "pihole" using Web option
        $command = 'export TERM=dumb && sudo pihole -d -w';

        // Add auto-upload option
        if (isset($params['upload'])) {
            $command .= ' -a';
        }

        // Execute database integrity_check
        if (isset($params['dbcheck'])) {
            $command .= ' -c';
        }

        $proc = popen($command, 'rb');

        while (!feof($proc)) {
            $this->formatDebugText(fread($proc, 4096), $params);
        }
    }

    /**
     * @param $dataText
     * @param $params
     * @return void
     */
    protected function formatDebugText($dataText, $params)
    {
        $keys = [];
        foreach (static::$ANSIcolors as $key => $value) {
            $keys[chr(27) . $key] = $value;
        }
        unset($key);
        $data = strtr(htmlspecialchars($dataText), $keys);

        if (!isset($params['IE'])) {
            echo 'data: ' . implode("\ndata: ", explode("\n", $data)) . "\n\n";
        } else {
            echo $data;
        }
    }
}
