<?php

namespace App\API\Group;

use App\API\GroupPostHandler;
use App\DB\SQLiteDB;
use App\Helper\Helper;

class Group extends GroupPostHandler
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

    public function getGroups($postData)
    {
        $resultSet = $this->gravity->doQuery('SELECT * FROM "group";');
        $data = ['data' => []];
        while ($res = $resultSet->fetchArray(SQLITE3_ASSOC)) {
            $data['data'][] = $res;
        }

        return $data;
    }

    public function addGroup($postData)
    {
        $input = html_entity_decode(trim($postData['name']));
        $names = str_getcsv($input, ' ');
        $total = count($names);
        $added = 0;
        $query = 'INSERT INTO "group" (name, description) VALUES (:name,:desc)';
        $stmt = $this->gravity->getDb()->prepare($query);

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
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            $stmt->reset();
            ++$added;
        }

        if ($added === $total) {
            return ['success' => true];
        }

        return Helper::returnJSONError('Not all groups added successfully', $postData);
    }

    public function editGroup($postData)
    {
        $name = html_entity_decode($postData['name']);
        $desc = html_entity_decode($postData['desc']);
        $query = 'UPDATE "group" SET enabled=:enabled, name=:name, description=:desc WHERE id = :id';

        $status = ((int)$postData['status']) !== 0 ? 1 : 0;
        $queryParams = [
            ':enabled' => $status,
            ':name'    => $name,
            ':desc'    => $desc,
            ':id'      => (int)$postData['id']
        ];

        $this->gravity->doQuery($query, $queryParams);

        return ['success' => true];
    }

    public function deleteGroups($postData)
    {
        $groups = json_decode($postData['id'], true, 512, JSON_THROW_ON_ERROR);
        $groups = implode(',', $groups);
        $tables = [
            'domainlist_by_group' => 'group_id',
            'client_by_group'     => 'group_id',
            'adlist_by_group'     => 'group_id',
            '"group"'             => 'id'
        ]; // quote reserved word
        foreach ($tables as $table => $column) {
            $query = sprintf(
                'DELETE FROM %s WHERE %s IN (%s)',
                $table,
                $column,
                $groups
            );
            $this->gravity->doQuery($query);
        }

        return ['success' => true];
    }
}
