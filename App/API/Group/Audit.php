<?php

namespace App\API\Group;

use App\API\GroupPostHandler;
use App\DB\SQLiteDB;

class Audit extends GroupPostHandler
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
     * @param array $postData
     * @return array
     */
    public function addAudit($postData)
    {
        $domains = explode(' ', html_entity_decode(trim($postData['domain'])));
        $before = $this->gravity->doQuery('SELECT COUNT(*) FROM domain_audit;');
        $before = $before->fetchArray()[0];
        $total = count($domains);
        $query = 'REPLACE INTO domain_audit (domain) VALUES (:domain)';


        foreach ($domains as $domain) {
            // Silently skip this entry when it is empty or not a string (e.g. NULL)
            if (!is_string($domain) || strlen($domain) == 0) {
                continue;
            }

            $this->gravity->doQuery($query, [':domain' => $domain]);
        }

        $after = $this->gravity->doQuery('SELECT COUNT(*) FROM domain_audit;');
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
}
