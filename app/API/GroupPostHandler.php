<?php

namespace App\API;

use App\API\Group\AdLists;
use App\API\Group\Client;
use App\API\Group\Domain;
use App\API\Group\Group;
use App\DB\SQLiteDB;
use App\Helper\Helper;
use App\PiHole as GlobalPiHole;
use JsonException;
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
     * @throws JsonException
     */
    public function postHandler(RequestInterface $request, ResponseInterface $response, $args)
    {
        $postData = $request->getParsedBody();
        $reload = false;
        $return = ['data' => [], 'message' => ''];
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
        if (strpos($postData['action'], 'adlist') > 0) {
            $handler = new AdLists();
            switch ($postData['action']) {
                case 'get_adlists':
                    $return = $handler->getAdLists($postData);
                    break;
                case 'add_adlist':
                    $return = $handler->addAdLists($postData);
                    $reload = true;
                    break;
                case 'delete_adlist':
                    $return = $handler->deleteAdList($postData);
                    $reload = true;
                    break;
                case 'edit_adlist':
                    $return = $handler->editAdList($postData);
                    $reload = true;
                    break;
                default:
                    $return = Helper::returnJSONError('No valid parameters supplied');
                    break;
            }
        }
        if (strpos($postData['action'], 'domain') > 0) {
            $handler = new Domain();
            switch ($postData['action']) {
                case 'get_domains':
                    $return = $handler->getDomains($postData);
                    break;
                case 'add_domain':
                    $return = $handler->addDomain($postData);
                    $reload = true;
                    break;
                case 'replace_domain':
                    $return = $handler->replaceDomain($postData);
                    $reload = true;
                    break;
                case 'edit_domain':
                    $return = $handler->editDomain($postData);
                    $reload = true;
                    break;
                case 'delete_domain':
                    $return = $handler->deleteDomain($postData);
                    $reload = true;
                    break;
                default:
                    $return = Helper::returnJSONError('No valid parameters supplied');
                    break;
            }
        }


        if ($reload) {
            $return['message'] = GlobalPiHole::execute('restartdns reload-lists');
        }

        return $this->returnAsJSON($request, $response, $return);
    }
}
