<?php

namespace app\Model;

class DNSRecord
{
    /**
     * CNAME, A, AAAA
     * @var string
     */
    private $type;

    /**
     * Name to resolve
     * @var string
     */
    private $name;

    /**
     * Target, e.g. an IP or a domain in case of a CNAME
     * @var string
     */
    private $target;

    public function __construct($params)
    {
        $this->type = trim($params['type']);
        $this->name = trim($params['name']);
        $this->target = trim($params['target']);
    }

    /**
     * @return mixed|string
     */
    public function getType(): mixed
    {
        return $this->type;
    }

    /**
     * @param mixed|string $type
     */
    public function setType(mixed $type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed|string
     */
    public function getName(): mixed
    {
        return $this->name;
    }

    /**
     * @param mixed|string $name
     */
    public function setName(mixed $name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed|string
     */
    public function getTarget(): mixed
    {
        return $this->target;
    }

    /**
     * @param mixed|string $target
     */
    public function setTarget(mixed $target): void
    {
        $this->target = $target;
    }

}