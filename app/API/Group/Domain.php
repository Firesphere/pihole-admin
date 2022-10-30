<?php

namespace App\API\Group;

use App\API\GroupPostHandler;
use App\DB\SQLiteDB;
use App\Helper\Helper;
use InvalidArgumentException;
use JsonException;
use SQLiteException;

/**
 *
 */
class Domain extends GroupPostHandler
{
    /**
     * @var array
     */
    protected static $listTypes = [
        'white' => SQLiteDB::LISTTYPE_WHITELIST,
        'black' => SQLiteDB::LISTTYPE_BLACKLIST
    ];
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
    public function getDomains($postData)
    {
        $where = '';
        $params = [];
        if (isset($postData['type']) && is_numeric($postData['type'])) {
            $where = 'WHERE type = :type';
            $params = ['type' => $postData['type']];
        }
        $query = sprintf('SELECT * FROM domainlist %s', $where);
        $query = $this->gravity->doQuery($query, $params);

        $data = [];
        while ($res = $query->fetchArray(SQLITE3_ASSOC)) {
            $group_query = 'SELECT group_id FROM domainlist_by_group WHERE domainlist_id = :id;';
            $params = [':id' => $res['id']];
            $groupResult = $this->gravity->doQuery($group_query, $params);

            $groups = [];
            while ($line = $groupResult->fetchArray(SQLITE3_ASSOC)) {
                $groups[] = $line['group_id'];
            }
            $res['groups'] = $groups;
            if ($res['type'] === SQLiteDB::LISTTYPE_WHITELIST || $res['type'] === SQLiteDB::LISTTYPE_BLACKLIST) {
                // Convert domain name to international form
                // Skip this for the root zone `.`
                if ($res['domain'] !== '.') {
                    $utf8_domain = Helper::convertIDNAToUnicode($res['domain']);

                    // if domain and international form are different, show both
                    if ($res['domain'] !== $utf8_domain) {
                        $res['domain'] = $utf8_domain . ' (' . $res['domain'] . ')';
                    }
                }
            }
            // Prevent domain and comment fields from returning any arbitrary javascript code which could be executed on the browser.
            $res['domain'] = htmlentities((string)$res['domain']);
            $res['comment'] = htmlentities((string)$res['comment']);
            $data[] = $res;
        }

        return ['data' => $data];
    }


    /**
     * @param $postData
     * @return array|bool[]
     */
    public function addDomain($postData)
    {
        $domains = explode(' ', html_entity_decode(trim($postData['domain'])));
        $before = $this->gravity->doQuery('SELECT COUNT(*) FROM domainlist;');
        $before = $before->fetchArray()[0]; // First result. There should only be one
        $total = count($domains);
        $added = 0;


        // Prepare INSERT INTO statement

        if (isset($postData['type'])) {
            $type = (int)$postData['type'];
        }
        if (isset($postData['list'])) {
            $type = static::$listTypes[$postData['list']];
        }


        $comment = html_entity_decode($postData['comment']);

        foreach ($domains as $domain) {
            // Silently skip this entry when it is empty or not a string (e.g. NULL)
            if (!is_string($domain) || strlen($domain) == 0) {
                continue;
            }

            if (isset($postData['type']) && !in_array((int)$postData['type'], [2, 3])) {
                // If not adding a RegEx....
                $input = $domain;
                // Convert domain name to IDNA ASCII form for international domains
                // Skip this for the root zone `.`
                if ($domain !== '.') {
                    $domain = Helper::convertUnicodeToIDNA($domain);
                }
                // convert the domain lower case and check whether it is valid
                $domain = strtolower($domain);
                $msg = '';
                if (!Helper::validDomain($domain, $msg)) {
                    $converted = htmlentities(utf8_encode($domain));
                    if ($input !== $domain) {
                        $converted = sprintf('(converted to "%s")', $converted);
                    }
                    $errormsg = sprintf('Domain %s is not a valid domain because: %s.', $converted, $msg);
                    $exc = sprintf('%s<br />Added %d out of %d domains.', $errormsg, $added, $total);
                    throw new InvalidArgumentException($exc);
                }
            }

            if (isset($postData['type']) &&
                strlen($postData['type']) === 2 &&
                $postData['type'][1] === 'W'
            ) {
                $escapedDomain = str_replace('.', '\\.', $domain);
                // Apply wildcard-style formatting
                $domain = sprintf('(\\.|^)%s$', $escapedDomain);
            }
            $params = [
                ':domain'  => $domain,
                ':type'    => $type,
                ':comment' => $comment
            ];

            $insert_stmt = 'INSERT OR IGNORE INTO domainlist (domain, type, comment) VALUES (:domain, :type, :comment)';

            $this->gravity->doQuery($insert_stmt, $params);

            ++$added;
        }

        $after = $this->gravity->doQuery('SELECT COUNT(*) FROM domainlist;');
        $after = $after->fetchArray()[0];
        $difference = $after - $before;
        $msgPart = '';
        if ($total === 1) {
            if ($difference !== 1) {
                $msg = 'Not adding %s as it is already on the list.';
            } else {
                $msg = 'Added %s.';
            }
            $msg = sprintf($msg, $domain);
        } elseif ($difference !== $total) {
            $msgPart = ' (skipped duplicates)';
        }
        if (!isset($msg)) {
            $msg = sprintf('Added %d out of %d domains%s.', $difference, $total, $msgPart);
        }

        return ['success' => true, 'message' => $msg];
    }

    /**
     * @param $postData
     * @return bool[]
     */
    public function replaceDomain($postData)
    {
        $domains = explode(' ', html_entity_decode(trim($postData['domain'])));
        $total = count($domains);
        $added = 0;

        // Prepare INSERT INTO statement

        if (isset($postData['type'])) {
            $type = (int)$postData['type'];
        }
        if (isset($postData['list'])) {
            $type = static::$listTypes[$postData['list']];
        }


        $comment = html_entity_decode($postData['comment']);

        foreach ($domains as $domain) {
            // Check statement will reveal any group associations for a given (domain,type) which do NOT belong to the default group
            $check_stmt = '
                    SELECT EXISTS(
                        SELECT domain FROM domainlist_by_group dlbg 
                            JOIN domainlist dl on dlbg.domainlist_id = dl.id 
                      WHERE dl.domain = :domain AND dlbg.group_id != 0
                    )';
            // Delete statement will remove this domain from any type of list
            $check_result = $this->gravity->doQuery($check_stmt, [':domain' => $domain]);

            if (!$check_result) {
                $exc = sprintf('While executing check: <strong>%s</strong>,<br />replaced %d out of %d domains', $this->gravity->getDb()->lastErrorMsg(), $added, $total);
                throw new SQLiteException($exc);
            }
            $params = [
                ':domain'  => $domain,
                ':type'    => $type,
                ':comment' => $comment
            ];
            // Check return value of CHECK query (0 = only default group, 1 = special group assignments)
            $only_default_group = $check_result->fetchArray(SQLITE3_NUM)[0] === 0;
            if (!$only_default_group) {
                $update_stmt = 'UPDATE domainlist SET comment = :comment WHERE domain = :domain AND type = :type';
                // Update only the comment, if it's not just in the default group.
                $this->gravity->doQuery($update_stmt, $params);
            } else {
                $delete_stmt = 'DELETE FROM domainlist WHERE domain = :domain';
                // Otherwise, delete it.
                $this->gravity->doQuery($delete_stmt, [':domain' => $domain]);
            }

            $insert_stmt = 'INSERT OR IGNORE INTO domainlist (domain, type, comment) VALUES (:domain, :type, :comment)';

            $this->gravity->doQuery($insert_stmt, $params);

            ++$added;
        }

        return ['success' => true];
    }

    /**
     * @param $postData
     * @return bool[]
     * @throws JsonException
     */
    public function deleteDomain($postData)
    {
        $groups = json_decode($postData['id'], true, 512, JSON_THROW_ON_ERROR);
        $groups = implode(',', $groups);
        $tables = [
            'domainlist_by_group' => 'domainlist_id',
            'domainlist'          => 'id'
        ]; // quote reserved word
        foreach ($tables as $table => $column) {
            $query = sprintf('DELETE FROM %s WHERE %s IN (%s)', $table, $column, $groups);
            $this->gravity->doQuery($query);
        }

        return ['success' => true];
    }

    /**
     * @param $postData
     * @return bool[]
     */
    public function editDomain($postData)
    {
        $updateQuery = '
            UPDATE domainlist 
            SET 
                enabled=:enabled, 
                comment=:comment, 
                type=:type 
            WHERE id = :id';


        $status = (int)$postData['status'] === 0 ? 0 : 1;
        $params = [
            ':enabled' => $status,
        ];

        $params['comment'] = html_entity_decode((string)$postData['comment']);
        $params[':type'] = (int)$postData['type'];
        $id = (int)$postData['id'];
        $params[':id'] = $id;

        $this->gravity->doQuery($updateQuery, $params);

        $this->gravity->doQuery('DELETE FROM domainlist_by_group WHERE domainlist_id = :id', [':id' => $id]);

        if (isset($postData['groups'])) {
            $groups = $postData['groups'];
            $groupQuery = 'INSERT INTO domainlist_by_group (domainlist_id,group_id) VALUES(:id,:gid);';
            foreach ($groups as $gid) {
                $params = [
                    ':id'  => $id,
                    ':gid' => $gid
                ];
                $this->gravity->doQuery($groupQuery, $params);
            }
        }

        return ['success' => true];
    }
}
