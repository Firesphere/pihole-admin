<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Queries extends Frontend
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        $params = $request->getQueryParams();
        $this->menuItems['Queries'] = 'active menu-open';
        $this->getShowingString($params);


        return $view->render($response, 'Pages/Queries.twig', $this->menuItems);
    }

    /**
     * Build the "showing X for Y" string from query params
     * It's a bit messy
     * @param array $params
     * @return void
     */
    public function getShowingString(array $params): void
    {
        $showing = ['showing'];
        $showall = false;


        $varsOptions = [
            'API_QUERY_LOG_SHOW' => [
                'permittedonly' => 'showing permitted',
                'blockedonly'   => 'blockedonly',
                'nothing'       => 'showing no queries (due to setting)'
            ]
        ];

        if (isset($this->setupVars['API_QUERY_LOG_SHOW'])) {
            $setting = $this->setupVars['API_QUERY_LOG_SHOW'];
            if (isset($varsOptions['API_QUERY_LOG_SHOW'][$setting])) {
                $showing = [$varsOptions['API_QUERY_LOG_SHOW'][$setting]];
            }
        }
        if (isset($params['type']) && $params['type'] === 'blocked') {
            $showing = ['showing blocked'];
        }

        $paramTypes = [
            'all'         => [
                'default' => 'all queries within the Pi-hole log%s'
            ],
            'client'      => [
                'default' => 'queries for client %s, <a href="queries/?client=%s&%s">%%s</a>'
            ],
            'forwarddest' => [
                'blocked' => 'queries blocked by Pi-hole%s',
                'cached'  => 'queries answered from cache%s',
                'default' => 'queries for upstream destination %s'
            ],
            'querytype'   => [
                'default' => 'type %s queries'
            ],
            'domain'      => [
                'default' => 'queries for domain %s'
            ],
            'from'        => [
                'default' => 'queries within specified time interval%s'
            ],
            'until'       => [
                'default' => 'queries within specified time interval%s'
            ],
        ];
        foreach ($params as $param => $value) {
            $tmpParam = 'default';
            $replace = '';
            switch ($param) {
                case 'forwarddest':
                    $tmpParam = $paramTypes['forwarddest'][$value] ?? 'default';
                    $replace = htmlentities($params['forwarddest']);
                    break;
                case 'querytype':
                    $replace = self::getQueryTypeString($params['querytype']);
                    break;
                case 'client':
                    $type = 'type=blocked';
                    $client = htmlentities($value);
                    $replace = 'show blocked only';
                    if (isset($params['type']) && $params['type'] === 'blocked') {
                        $type = '';
                        $replace = 'show all';
                    }
                    $paramTypes[$param][$tmpParam] = sprintf(
                        $paramTypes[$param][$tmpParam],
                        $client,
                        $client,
                        $type
                    );
                    break;
                case 'domain':
                    $replace = htmlentities($params['domain']);
                    break;
                case 'from':
                case 'until':
                    break;
            }

            if (isset($paramTypes[$param])) {
                $showing[] = sprintf($paramTypes[$param][$tmpParam], $replace);
            }
        }

        if (count($showing) === 1) {
            $showing[] = 'up to 100 queries';
            $showall = true;
        }

        if (!empty($showing)) {
            $showing = '(' . implode(' ', $showing) . ')';
            if ($showall) {
                $showing = sprintf('%s, <a href="?all">show all</a>', $showing);
            }
        }

        $this->menuItems['Showing'] = $showing;
    }
}