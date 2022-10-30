<?php

namespace App\API\Group;

use App\API\GroupPostHandler;
use App\DB\SQLiteDB;
use App\Helper\Helper;
use InvalidArgumentException;
use JsonException;

/**
 *
 */
class AdLists extends GroupPostHandler
{
    /**
     * @var SQLiteDB
     */
    protected $gravity;

    /**
     *
     */
    public function __construct()
    {
        $this->gravity = new SQLiteDB('GRAVITYDB', SQLITE3_OPEN_READWRITE);
        $this->db = new SQLiteDB('FTLDB', SQLITE3_OPEN_READWRITE);
    }

    /**
     * @param $postData
     * @return array|array[]
     */
    public function getAdLists($postData)
    {
        $query = $this->gravity->doQuery('SELECT * FROM adlist;');

        $data = ['data' => []];
        while ($res = $query->fetchArray(SQLITE3_ASSOC)) {
            $group_query = 'SELECT group_id FROM adlist_by_group WHERE adlist_id = :id;';
            $groupResult = $this->gravity->doQuery($group_query, [':id' => $res['id']]);

            $groups = [];
            while ($gres = $groupResult->fetchArray(SQLITE3_ASSOC)) {
                $groups[] = $gres['group_id'];
            }
            $res['groups'] = $groups;
            $data['data'][] = $res;
        }

        return $data;
    }

    /**
     * @param $postData
     * @return array
     */
    public function addAdLists($postData)
    {
        $addresses = explode(' ', html_entity_decode(trim($postData['address'])));
        $total = count($addresses);
        $added = 0;
        $ignored = 0;

        $query = 'INSERT INTO adlist (address,comment) VALUES (:address,:comment)';

        $params['comment'] = html_entity_decode((string)$postData['comment']);

        $added_list = [];
        $ignored_list = [];
        foreach ($addresses as $address) {
            // Silently skip this entry when it is empty or not a string (e.g. NULL)
            if (!is_string($address) || $address === '') {
                continue;
            }

            // this will remove first @ that is after schema and before domain
            // $1 is optional schema, $2 is userinfo
            $check_address = preg_replace('|([^:/]*://)?([^/]+)@|', '$1$2', $address, 1);

            if (preg_match('/[^a-zA-Z0-9:\\/?&%=~._()-;]/', $check_address) !== 0) {
                $exc = sprintf('<strong>Invalid adlist URL %s</strong><br>Added %d out of %d adlists',
                    htmlentities(implode('', $added_list)),
                    $added,
                    $total
                );
                throw new InvalidArgumentException($exc);
            }

            $params['address'] = $address;

            if (!$this->gravity->doQuery($query, $params)) {
                if ($this->gravity->getDb()->lastErrorCode() === 19) {
                    // ErrorCode 19 is "Constraint violation", here the unique constraint of `address`
                    //   is violated (https://www.sqlite.org/rescode.html#constraint).
                    // If the list is already in database, add to ignored list, but don't throw error
                    ++$ignored;
                    $ignored_list[] = '<small>' . $address . '</small><br>';
                }
            } else {
                ++$added;
                $added_list[] = '<small>' . $address . '</small><br>';
            }
        }

        if (count($ignored_list)) {
            $msg = sprintf(
                '<b>Ignored duplicate adlists: %d</b> %s<br /><b>Added adlists: %d</b> %s<br />Total: %d adlist(s) processed.',
                $ignored,
                implode('', $ignored_list),
                $added,
                implode('', $added_list),
                $total
            );

            return Helper::returnJSONWarning($msg, $postData);
        }   // All adlists added
        $msg = sprintf(
            '%s<br /><b>Total:</b> %d adlist(s) processed',
            implode('', $added_list),
            $total
        );

        return ['success' => true, 'message' => $msg];
    }

    /**
     * @param $postData
     * @return bool[]
     * @throws JsonException
     */
    public function deleteAdList($postData)
    {
        // Accept only an array
        $ids = json_decode($postData['id'], false, 512, JSON_THROW_ON_ERROR);

        // Exploit prevention: Ensure all entries in the ID array are integers
        foreach ($ids as $value) {
            if (!is_numeric($value)) {
                throw new InvalidArgumentException('Invalid payload: id contains non-numeric entries');
            }
        }

        // Delete from: adlists_by_group
        $this->gravity->doQuery('DELETE FROM adlist_by_group WHERE adlist_id IN (' . implode(',', $ids) . ')');

        // Delete from: adlists
        $this->gravity->doQuery('DELETE FROM adlist WHERE id IN (' . implode(',', $ids) . ')');

        return ['success' => true];
    }

    public function editAdList($postData)
    {

        $updateQuery = 'UPDATE adlist SET enabled=:enabled, comment=:comment WHERE id = :id';

        $status = (int)$postData['status'] === 0 ? 0 : 1;
        $params = [
            ':enabled' => $status,
        ];


        $params[':comment'] = html_entity_decode((string)$postData['comment']);
        $params[':id'] = (int)$postData['id'];

        $this->gravity->doQuery($updateQuery, $params);


        $this->gravity->doQuery(
            'DELETE FROM adlist_by_group WHERE adlist_id = :id',
            [':id' => $params[':id']]
        );

        if (isset($postData['groups'])) {
            $groups = $postData['groups'];
            $query = 'INSERT INTO adlist_by_group (adlist_id,group_id) VALUES(:id,:gid);';
            $params = [
                ':id' => (int)$postData['id']
            ];
            foreach ($groups as $gid) {
                $params[':gid'] = $gid;
                $this->gravity->doQuery($query, $params);
            }
        }

        return ['success' => true];
    }
}
