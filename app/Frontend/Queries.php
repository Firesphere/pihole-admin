<?php

namespace App\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class Queries
{
    /**
     * @var array|false
     */
    private $setupVars = [];

    public function __construct()
    {
        $this->setupVars = parse_ini_file(__DIR__ . '/../../setupVars.ini');
    }

    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $view = Twig::fromRequest($request);
        parse_str($request->getUri()->getQuery(), $params);

        $showing = 'showing';
        $paramOptions = [
            'forwarddest' => [
                'blocked' => ' queries blocked by Pi-hole',
                'cached'  => ' queries answered from cache',
                'default' => ' queries for upstream destination ' . !isset($params['forwarddest']) ? '' : htmlentities($params['forwarddest'])
            ],
            'API_QUERY_LOG_SHOW' => [
                'permittedonly' => 'showing permitted',
                'blockedonly' => 'blockedonly',
                'nothing' => 'showing no queries (due to setting)'
            ]
        ];

        if (isset($this->setupVars['API_QUERY_LOG_SHOW'])) {
            $setting = $this->setupVars['API_QUERY_LOG_SHOW'];
            if (isset($paramOptions['API_QUERY_LOG_SHOW'][$setting])) {
                $showing = $paramOptions['API_QUERY_LOG_SHOW'][$setting];
            }
        }
        if (isset($params['type']) && $params['type'] === 'blocked') {
            $showing = 'showing blocked';
        }

        // @todo this could be a bit cleaner, but works for now
        $showall = false;
        if (isset($params['all'])) {
            $showing .= ' all queries within the Pi-hole log';
        }
        if (isset($params['client'])) {
            $urltype = '&type=blocked';
            $urltext = 'show blocked only';
            // Add switch between showing all queries and blocked only
            if (isset($params['type']) && $params['type'] === 'blocked') {
                $urltype = '';
                $urltext = 'show all';
                // Show blocked queries for this client + link to all
            }
            // Show All queries for this client + link to show only blocked
            $showing .= ' queries for client ' . htmlentities($params['client']);
            $showing .= ', <a href="?client=' . htmlentities($params['client']) . $urltype . '">' . $urltext . '</a>';
        }

        if (isset($params['forwarddest'])) {
            $fwdestparam = in_array($params['forwarddest'], $paramOptions['forwarddest'], true) ? $params['forwarddest'] : 'default';
            $showing .= $paramOptions['forwarddest'][$fwdestparam];
        } elseif (isset($params['querytype'])) {
            $showing .= ' type ' . getQueryTypeStr($params['querytype']) . ' queries';
        } elseif (isset($params['domain'])) {
            $showing .= ' queries for domain ' . htmlentities($params['domain']);
        } elseif (isset($params['from']) || isset($params['until'])) {
            $showing .= ' queries within specified time interval';
        } else {
            $showing .= ' up to 100 queries';
            $showall = true;
        }

        if (!empty($showing)) {
            $showing = '(' . $showing . ')';
            if ($showall) {
                $showing .= ', <a href="?all">show all</a>';
            }
        }

        return $view->render($response, 'Pages/Queries.twig', ['Showing' => $showing]);
    }
}