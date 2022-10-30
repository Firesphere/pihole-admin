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
        1 => 'A',
        2 => 'AAAA',
        3 => 'ANY',
        4 => 'SRV',
        5 => 'SOA',
        6 => 'PTR',
        7 => 'TXT',
        8 => 'NAPTR',
        9 => 'MX',
        10 => 'DS',
        11 => 'RRSIG',
        12 => 'DNSKEY',
        13 => 'NS',
        14 => 'OTHER',
        15 => 'SVCB',
        16 => 'HTTPS'
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
        return static::$querytypes[$qtype] ?? sprintf('TYPE(%s)', $qtype - 100);
    }
}
