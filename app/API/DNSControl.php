<?php

namespace App\API;

use App\Helper\Helper;
use App\Model\DNSRecord;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DNSControl extends APIBase
{
    private const CUSTOM_DNS_FILE = '/etc/pihole/custom.list';
    private const CUSTOM_CNAME_FILE = '/etc/dnsmasq.d/05-pihole-custom-cname.conf';

    /**
     * @var array|DNSRecord[]
     */
    protected static array $existing_records = [];

    public function __construct()
    {
        if (!count(static::$existing_records)) {
            if (file_exists(static::CUSTOM_DNS_FILE)) {
                static::readEntries(static::CUSTOM_CNAME_FILE);
            }
            if (file_exists(static::CUSTOM_DNS_FILE)) {
                static::readEntries(static::CUSTOM_DNS_FILE);
            }
        }
    }

    public static function readEntries($file)
    {
        $handle = fopen($file, 'rb');
        $type = ($file === static::CUSTOM_CNAME_FILE) ? 'CNAME' : 'IP';
        $explode = $type === 'IP' ? ' ' : ',';
        while ($line = fgets($handle)) {
            $line = str_replace('cname=', '', trim($line));
            $explodedLine = explode($explode, $line);

            if (count($explodedLine) <= 1) {
                continue;
            }

            $recordType = $type === 'IP' ? 'A' : 'CNAME';

            // A record but not an IPv4
            if ($recordType === 'A' &&
                !filter_var($explodedLine[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            ) {
                $recordType = 'AAAA';
            }

            $data = new DNSRecord([
                'name'   => $explodedLine[0],
                'target' => $explodedLine[1],
                'type'   => $recordType
            ]);
            static::$existing_records[$type] = $data;
        }

        fclose($handle);
    }

    /**
     * Add a $name to point to $target, of $type
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param $args
     * @return ResponseInterface
     */
    public function addRecord(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        $name = $args['domain'];
        $target = $args['target'];
        $type = 'A';
        // Validate domain and IP of target
        if (!filter_var($name, FILTER_VALIDATE_DOMAIN)) {
            throw new InvalidArgumentException('Invalid domain name');
        }
        if (
            !filter_var($target, FILTER_VALIDATE_DOMAIN) &&
            !filter_var($target, FILTER_VALIDATE_IP)
        ) {
            throw new InvalidArgumentException('Invalid target');
        }
        if (filter_var($target, FILTER_VALIDATE_DOMAIN)) {
            $type = 'CNAME';
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
                throw new InvalidArgumentException('Record already exists');
            }
        }

        $record->save();

        static::$existing_records[] = $record;

        $body = $response->getBody();
        $body->write(json_encode(['success' => true, 'message' => '']));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Delete a $name to point to $target, of $type
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param $args
     * @return ResponseInterface
     */
    public function deleteRecord(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $name = $args['domain'];
        $target = $args['target'];
        // Validate domain and IP of the domain
        if (!filter_var($name, FILTER_VALIDATE_DOMAIN)) {
            throw new InvalidArgumentException('Invalid domain name');
        }
        if (
            (!filter_var($target, FILTER_VALIDATE_DOMAIN)) &&
            (!filter_var($target, FILTER_VALIDATE_IP))
        ) {
            throw new InvalidArgumentException('Invalid target');
        }
        /** @var DNSRecord $existing */
        foreach (static::$existing_records as $key => $existing) {
            if ($existing->getName() === $name &&
                $existing->getTarget() === $target
            ) {
                $existing->delete();
                unset(static::$existing_records[$key]);
                break;
            }
        }
        $body = $response->getBody();
        $body->write(json_encode(['success' => true, 'message' => '']));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getAsJSON(ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        $type = strtoupper($params['type']);
        $list = static::getExistingRecords();

        if (!isset($list[$type])) {
            $msg = Helper::returnJSONError('No custom records');
            $return = array_merge($msg, ['data' => []]);
            return $this->returnAsJSON($request, $response, $return);
        }

        return $this->returnAsJSON($request, $response, $list[$type]);
    }

    /**
     * @return array
     */
    public static function getExistingRecords(): array
    {
        return self::$existing_records;
    }

    public function deleteAll(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $type = $args['type'];
        foreach (static::$existing_records as $key => $record) {
            if ($record->getType() === $type) {
                $record->delete();
                unset(static::$existing_records[$key]);
            }
        }

        $body = $response->getBody();
        $body->write(json_encode(['success' => true, 'message' => '']));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
