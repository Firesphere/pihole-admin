<?php

namespace app\API;

use app\Model\DNSRecord;

class DNSControl
{
    private const CUSTOM_DNS_FILE = '/etc/pihole/custom.list';
    /**
     * Add a $name to point to $target, of $type
     * @param string $name Domain name
     * @param string $target Target (e.g. Domain or IP)
     * @param string $type CNAME or A or AAAA
     * @return void
     */
    public function addRecord($name, $target, $type)
    {
        $record = new DNSRecord([
            'type'   => $type,
            'target' => $target,
            'name'   => $name
        ]);

        $this->prepareCommand('add', $record);
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

    }

    public function listRecords()
    {
        $entries = [];
        $handle = fopen(self::CUSTOM_DNS_FILE, 'r');
        if ($handle) {
            while ($line = fgets($handle)) {
                $line = trim($line);
                [$target, $name] = explode(' ', $line);

                if (!$target || !$name) {
                    continue;
                }

                $data = new DNSRecord(['name' => $name, $target]);
                $entries[] = $data;
            }

            fclose($handle);
        }

        return $entries;
    }

    /**
     * @param string $method Add or Delete
     * @param DNSRecord $data
     * @return string
     */
    private function prepareCommand(string $method, DNSRecord $data)
    {
        $dnsCmd = 'customdns';
        if ($data->getType() === 'CNAME') {
            $dnsCmd = 'customcname';
        }
        switch ($method) {
            case 'add':
                $cmd = sprintf('-a add%s %s %s %s',
                    $dnsCmd,
                    $data->getTarget(),
                    $data->getName(),
                    true
                );
                break;
            case 'delete':
                $cmd = sprintf('-a remove%s %s %s',
                    $dnsCmd,
                    $data->getTarget(),
                    $data->getName(),
                );
                break;
            default:
                throw new \LogicException('No valid DNS Record given.');
        }

        return $cmd;
    }
}