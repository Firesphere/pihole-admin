<?php

namespace App\Frontend\Settings;

use App\DB\SQLiteDB;

/**
 * Quick helper to keep the data out of the Teleporter.
 * Otherwise, the listing out of queries would take over
 * the size
 */
class ImportQueryHelper
{
    /**
     * @var \string[][]
     */
    public static $queryParts = [
        'adlist'              => [
            'fields' => '(id,address,enabled,date_added,comment)',
            'values' => '(:id,:address,:enabled,:date_added,:comment)',
            'type'   => -1 // Type is not used here
        ],
        'domain_audit'        => [
            'fields' => '(id,domain,date_added)',
            'values' => '(:id,:domain,:date_added)',
            'type'   => -1 // Type is not used here
        ],
        'domainlist'          => [
            'fields' => '(id,domain,enabled,date_added,comment,type)',
            'values' => '(:id,:domain,:enabled,:date_added,:comment,:type)',
            'type'   => -1 // Type is not used here
        ],
        'group'               => [
            'fields' => '(id,name,date_added,description)',
            'values' => '(:id,:name,:date_added,:description)',
            'type'   => -1 // Type is not used here
        ],
        'client'              => [
            'fields' => '(id,ip,date_added,comment)',
            'values' => '(:id,:ip,:date_added,:comment)',
            'type'   => -1 // Type is not used here
        ],
        'domainlist_by_group' => [
            'fields' => '(domainlist_id,group_id)',
            'values' => '(:domainlist_id,:group_id)',
            'type'   => -1 // Type is not used here
        ],
        'client_by_group'     => [
            'fields' => '(client_id,group_id)',
            'values' => '(:client_id,:group_id)',
            'type'   => -1 // Type is not used here
        ],
        'adlist_by_group'     => [
            'fields' => '(adlist_id,group_id)',
            'values' => '(:adlist_id,:group_id)',
            'type'   => -1 // Type is not used here
        ],
    ];

    /**
     * @var array[]
     */
    public static $listTypes = [
        'whitelist'       => [
            'table' => 'domainlist',
            'type'  => SQLiteDB::LISTTYPE_WHITELIST
        ],
        'blacklist'       => [
            'table' => 'domainlist',
            'type'  => SQLiteDB::LISTTYPE_BLACKLIST
        ],
        'whitelist.exact' => [
            'table' => 'domainlist',
            'type'  => SQLiteDB::LISTTYPE_WHITELIST
        ],
        'blacklist.exact' => [
            'table' => 'domainlist',
            'type'  => SQLiteDB::LISTTYPE_BLACKLIST
        ],
        'whitelist.regex' => [
            'table' => 'domainlist',
            'type'  => SQLiteDB::LISTTYPE_REGEX_WHITELIST
        ],
        'blacklist.regex' => [
            'table' => 'domainlist',
            'type'  => SQLiteDB::LISTTYPE_REGEX_BLACKLIST
        ],
    ];
}
