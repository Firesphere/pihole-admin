<?php

namespace App\Frontend;

class Frontend
{
    /**
     * @var array|false
     */
    protected $setupVars = [];

    protected $menuItems = [];

    protected static $querytypes = [
        'A',
        'AAAA',
        'ANY',
        'SRV',
        'SOA',
        'PTR',
        'TXT',
        'NAPTR',
        'MX',
        'DS',
        'RRSIG',
        'DNSKEY',
        'NS',
        'OTHER',
        'SVCB',
        'HTTPS'
    ];

    public function __construct()
    {
        $this->setupVars = parse_ini_file(__DIR__ . '/../../setupVars.ini');
    }

    /**
     * Determine the query type by position in array.
     * Kind of "fingers crossed" method, but seems to work.
     * @param $query
     * @return string
     */
    public static function getQueryTypeString($query)
    {
        $qtype = (int)$query;
        if ($qtype > 0 && $qtype <= count(static::$querytypes)) {
            return static::$querytypes[$qtype - 1];
        }

        return 'TYPE' . ($qtype - 100);
    }
}