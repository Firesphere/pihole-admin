<?php

namespace app\API;

use app\Model\DNSRecord;

class DNSControl
{
    private const CUSTOM_DNS_FILE = '/etc/pihole/custom.list';
    private const CUSTOM_CNAME_FILE = '/etc/dnsmasq.d/05-pihole-custom-cname.conf';

    /**
     * @var array
     */
    protected static array $existing_records = [];

    public function __construct()
    {
        if (file_exists(static::CUSTOM_DNS_FILE)) {
            $this->readEntries(static::CUSTOM_CNAME_FILE);
        }
        if (file_exists(static::CUSTOM_DNS_FILE)) {
            $this->readEntries(static::CUSTOM_DNS_FILE);
        }
    }

    private function readEntries($file)
    {
        $handle = fopen($file, 'r');
        $type = ($file === static::CUSTOM_CNAME_FILE) ? 'CNAME' : 'IP';
        $explode = $type === 'IP' ? ' ' : ',';
        while ($line = fgets($handle)) {
            $line = str_replace('cname=', '', trim($line));
            $explodedLine = explode($explode, $line);

            if (count($explodedLine) <= 1) {
                continue;
            }

            if ($type === 'A' && !filter_var($explodedLine[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $type = 'AAAA';
            }

            $data = new DNSRecord([
                'name'   => $explodedLine[0],
                'target' => $explodedLine[1],
                'type'   => $type
            ]);
            static::$existing_records[] = $data;
        }

        fclose($handle);
    }

    /**
     * @return array
     */
    public static function getExistingRecords(): array
    {
        return self::$existing_records;
    }

    /**
     * Add a $name to point to $target, of $type
     * @param string $name Domain name
     * @param string $target Target (e.g. Domain or IP)
     * @param string $type CNAME or A or AAAA
     * @return void
     */
    public function addRecord($name, $target, $type)
    {
        // Validate domain and IP of target
        if (!filter_var($name, FILTER_VALIDATE_DOMAIN)) {
            throw new \InvalidArgumentException('Invalid domain name');
        }
        if (
            ($type === 'CNAME' && !filter_var($target, FILTER_VALIDATE_DOMAIN)) ||
            ($type !== 'CNAME' && !filter_var($target, FILTER_VALIDATE_IP))
        ) {
            throw new \InvalidArgumentException('Invalid target');
        }
        // IPv6 type checking
        if ($type === 'A' && !filter_var($type, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $type = 'AAAA';
        }

        $record = new DNSRecord([
            'type'   => $type,
            'target' => $target,
            'name'   => $name
        ]);

        foreach (static::$existing_records as $existing) {
            if ($existing->getName() === $name &&
                $existing->getTarget() === $target &&
                $existing->getType() === $type
            ) {
                throw new \InvalidArgumentException('Record already exists');
            }
        }

        $record->save();

        static::$existing_records[] = $record;
    }

    /**
     * Delete a $name to point to $target, of $type
     * @param string $name Domain name
     * @param string $target Target (e.g. Domain or IP)
     * @param string $type CNAME or A or AAAA
     * @return void
     */
    public function deleteRecord($name, $target, $type)
    {
        // Validate domain and IP of target
        if (!filter_var($name, FILTER_VALIDATE_DOMAIN)) {
            throw new \InvalidArgumentException('Invalid domain name');
        }
        if (
            ($type === 'CNAME' && !filter_var($target, FILTER_VALIDATE_DOMAIN)) ||
            ($type !== 'CNAME' && !filter_var($target, FILTER_VALIDATE_IP))
        ) {
            throw new \InvalidArgumentException('Invalid target');
        }
        /** @var DNSRecord $existing */
        foreach (static::$existing_records as $existing) {
            if ($existing->getName() === $name &&
                $existing->getTarget() === $target &&
                $existing->getType() === $type
            ) {
                $existing->delete();
                break;
            }
        }
    }
}
