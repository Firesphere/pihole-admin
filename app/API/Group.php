<?php

namespace App\API;

use App\DB\SQLiteDB;
use App\Helper\Helper;
use App\PiHole as GlobalPiHole;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Group extends APIBase
{
    /**
     * @var SQLiteDB
     */
    private $db;

    public function __construct()
    {
        $this->db = new SQLiteDB('GRAVITYDB');
    }

    public function postHandler(RequestInterface $request, ResponseInterface $response, $args)
    {
        $postData = $request->getParsedBody();
        switch ($postData['action']) {
            case 'get_groups':
                $return = $this->getGroups($postData);
                break;
            case 'add_group':
                $return = $this->addGroup($postData);
                break;
            default:
                $return = Helper::returnJSONError('No valid parameters supplied');
                break;
        }
        return $this->returnAsJSON($request, $response, $return);
    }

    private function getGroups($postData)
    {
        try {
            $resultSet = $this->db->doQuery('SELECT * FROM "group";');
            $data = ['data' => []];
            while ($res = $resultSet->fetchArray(SQLITE3_ASSOC)) {
                $data['data'][] = $res;
            }

            return $data;
        } catch (\Exception $ex) {
            return Helper::returnJSONError($ex->getMessage(), $postData);
        }
    }

    private function addGroup($postData)
    {
        try {
            $input = html_entity_decode(trim($postData['name']));
            $names = str_getcsv($input, ' ');
            $total = count($names);
            $added = 0;
            $query = 'INSERT INTO "group" (name,description) VALUES (:name,:desc)';

            $desc = $postData['desc'];
            if ($desc === '') {
                // Store NULL in database for empty descriptions
                $desc = null;
            }


            foreach ($names as $name) {
                $params = [
                    ':name' => $name,
                    ':desc' => $desc
                ];
                $this->db->doQuery($query, $params);
                ++$added;
            }

            GlobalPiHole::execute('restartdns reload-lists');
            if ($added === $total) {
                return ['success' => true];
            }
            return Helper::returnJSONError('Not all groups added successfully', $postData);
        } catch (\Exception $ex) {
            return Helper::returnJSONError($ex->getMessage(), $postData);
        }
    }

}