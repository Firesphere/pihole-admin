<?php

namespace App\API;

use App\Helper\Helper;
use App\Model\DNSRecord;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 *
 */
class DNSControl extends APIBase
{
    /**
     * Location of custom DNS records
     */
    private const CUSTOM_DNS_FILE = '/var/www/html/custom.list';
    /**
     * Location of custom CNAME records
     */
    private const CUSTOM_CNAME_FILE = '/var/www/html/05-pihole-custom-cname.conf';

    /**
     * @var array|DNSRecord[]
     */
    protected static array $existing_records = [];

    /**
     *
     */
    public function __construct()
    {
        if (!count(static::$existing_records)) {
            if (file_exists(static::CUSTOM_DNS_FILE)) {
                $this->readEntries(static::CUSTOM_CNAME_FILE, 'CNAME');
            }
            if (file_exists(static::CUSTOM_DNS_FILE)) {
                $this->readEntries(static::CUSTOM_DNS_FILE, 'IP');
            }
        }
    }

    /**
     * @param $file
     * @return void
     */
    protected function readEntries($file, $type)
    {
        $handle = fopen($file, 'rb');
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
                filter_var($explodedLine[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ) {
                $recordType = 'AAAA';
            }

            $data = new DNSRecord([
                'name'   => $explodedLine[1],
                'target' => $explodedLine[0],
                'type'   => $recordType
            ]);
            static::$existing_records[$type][] = $data;
        }

        fclose($handle);
    }

    /**
     * Add a $name to point to $target, of $type
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param $args
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function addRecord(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        $params = $request->getParsedBody();
        // Validate domain and IP of target
        if (!filter_var($params['domain'], FILTER_VALIDATE_DOMAIN) &&
            !filter_var($params['domain'], FILTER_VALIDATE_IP)
        ) {
            return $this->returnAsJSON($request, $response, ['success' => false, 'message' => 'Invalid domain name']);
        }
        if (
            !filter_var($params['target'], FILTER_VALIDATE_DOMAIN) &&
            !filter_var($params['target'], FILTER_VALIDATE_IP)
        ) {
            return $this->returnAsJSON($request, $response, ['success' => false, 'message' => 'Invalid target']);
        }

        // IPv6 type checking
        if (filter_var($params['target'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $type = 'A';
        } elseif (filter_var($params['target'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $type = 'AAAA';
        } elseif (filter_var($params['target'], FILTER_VALIDATE_DOMAIN)) {
            $type = 'CNAME';
        } else {
            return $this->returnAsJSON($request, $response, ['success' => false, 'message' => 'Invalid target type']);
        }

        $existingType = $type === 'CNAME' ? 'CNAME' : 'IP';

        foreach (static::$existing_records[$existingType] as $existing) {
            if ($existing->getName() === $params['domain'] &&
                $existing->getTarget() === $params['target'] &&
                $existing->getType() === $type
            ) {
                return $this->returnAsJSON($request, $response, ['success' => false, 'message' => 'Record already exists']);
            }
        }
        $record = new DNSRecord([
            'type'   => $type,
            'target' => $params['target'],
            'name'   => $params['domain']
        ]);


        $result = $record->save();
        if (isset($result['FTLnotrunning'])) {
            $result['success'] = false;

            return $this->returnAsJSON($request, $response, $result);
        }

        static::$existing_records[] = $record;

        return $this->returnAsJSON($request, $response, ['success' => true]);
    }

    /**
     * Delete a $name to point to $target, of $type
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function deleteRecord(ServerRequestInterface $request, ResponseInterface $response)
    {
        $args = $request->getParsedBody();
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
        $argType = $args['type'] === 'DNS' ? 'IP' : 'CNAME';
        /** @var DNSRecord $existing */
        foreach (static::$existing_records[$argType] as $key => $existing) {
            if ($existing->getName() === $name &&
                $existing->getTarget() === $target
            ) {
                $result = $existing->delete();
                unset(static::$existing_records[$key]);
                break;
            }
        }
        if (isset($result['FTLnotrunning'])) {
            $result['success'] = false;

            return $this->returnAsJSON($request, $response, $result);
        }

        return $this->returnAsJSON($request, $response, ['success' => true]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getAsJSON(ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        $type = strtoupper($params['type']) === 'DNS' ? 'IP' : 'CNAME';
        $list = static::getExistingRecords();

        if (!isset($list[$type])) {
            $msg = Helper::returnJSONError('No custom records');
            $return = array_merge($msg, ['data' => []]);

            return $this->returnAsJSON($request, $response, $return);
        }
        $data = [];
        /** @var DNSRecord $item */
        foreach ($list[$type] as $item) {
            $data[] = [
                $item->name,
                $item->target
            ];
        }

        return $this->returnAsJSON($request, $response, ['data' => $data]);
    }

    /**
     * @return array
     */
    public static function getExistingRecords(): array
    {
        return self::$existing_records;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param $args
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function deleteAll(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $type = $args['type'];
        foreach (static::$existing_records as $key => $record) {
            if ($record->getType() === $type) {
                $record->delete();
                unset(static::$existing_records[$key]);
            }
        }

        return $this->returnAsJSON($request, $response, ['success' => true]);
    }
}
