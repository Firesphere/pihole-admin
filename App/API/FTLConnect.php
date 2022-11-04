<?php

namespace App\API;

use App\PiHole;

class FTLConnect
{
    private const DEFAULT_FTL_IP = '127.0.0.1';
    private const DEFAULT_FTL_PORT = 4711;

    /**
     * @var resource|false
     */
    protected $socket;
    /**
     * @var int
     */
    protected $code = 0;
    /**
     * @var string
     */
    protected $message = '';


    public function connectFTL()
    {
        // We only use the default IP
        $address = self::DEFAULT_FTL_IP;

        // Try to read port from FTL config. Use default if not found.
        $config = PiHole::getConfig();

        // Use the port only if the value is numeric
        if (isset($config['FTLPORT']) && is_numeric($config['FTLPORT'])) {
            $port = (int)$config['FTLPORT'];
        } else {
            $port = self::DEFAULT_FTL_PORT;
        }

        // Open Internet socket connection
        $this->socket = fsockopen($address, $port, $this->code, $this->message, 1.0);
    }

    public function disconnectFTL()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

    /**
     * @return mixed
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    public static function getFTLData($pid, $cmd)
    {
        $command = sprintf('ps -p %d -o %s', $pid, $cmd);

        return trim(exec($command));
    }
}
