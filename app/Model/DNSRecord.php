<?php

namespace app\Model;

class DNSRecord
{
    /**
     * CNAME, A, AAAA
     * @var string
     */
    private $type = '';

    /**
     * Target, e.g. an IP or a domain in case of a CNAME
     * @var string
     */
    private $target = '';

    /**
     * Record in use
     * @var bool
     */
    private $active = true;

    public function __construct($params)
    {
        $this->type = $params['type'];
        $this->target = $params['target'];
        $this->active = $params['active'];
    }

}