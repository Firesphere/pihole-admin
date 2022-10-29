<?php

namespace App\API;

use App\API\Group\Client;
use App\API\Group\Group;
use App\DB\SQLiteDB;
use App\Helper\Helper;
use App\PiHole as GlobalPiHole;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interestingly, Group uses POST instead of GET...
 */
class GroupPostHandler extends APIBase
{
    /**
     * @var SQLiteDB
     */
    protected $db;

    /**
     * @throws \JsonException
     */
    public function postHandler(RequestInterface $request, ResponseInterface $response, $args)
    {
        $postData = $request->getParsedBody();
        $reload = false;
        $return = ['data' => []];
        if (strpos($postData['action'], 'group') > 0) {
            $handler = new Group();
            switch ($postData['action']) {
                case 'get_groups':
                    $return = $handler->getGroups($postData);
                    break;
                case 'add_group':
                    $return = $handler->addGroup($postData);
                    $reload = true;
                    break;
                case 'edit_group':
                    $return = $handler->editGroup($postData);
                    $reload = true;
                    break;
                case 'delete_group':
                    $return = $handler->deleteGroups($postData);
                    $reload = true;
                    break;
                default:
                    $return = Helper::returnJSONError('No valid parameters supplied');
                    break;
            }
        }
        if (strpos($postData['action'], 'client') > 0) {
            $handler = new Client();
            switch ($postData['action']) {
                case 'get_unconfigured_clients':
                    $return = $handler->getUnconfiguredClients($postData);
                    break;
                case 'get_clients':
                    $return = $handler->getClients($postData);
                    break;
                case 'add_client':
                    $return = $handler->addClient($postData);
                    $reload = true;
                    break;
                case 'delete_client':
                    $return = $handler->deleteClient($postData);
                    $reload = true;
                    break;
                case 'edit_client':
                    $return = $handler->editClient($postData);
                    $reload = true;
                    break;
                default:
                    $return = Helper::returnJSONError('No valid parameters supplied');
                    break;
            }
        }


        if ($reload) {
            GlobalPiHole::execute('restartdns reload-lists');
        }

        return $this->returnAsJSON($request, $response, $return);
    }
}