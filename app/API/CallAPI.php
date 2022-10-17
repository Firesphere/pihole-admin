<?php

namespace App\API;

class CallAPI
{

    protected $connection;

    public function __construct()
    {
        $this->connection = new FTLConnect();
    }

    public function doCall($call)
    {
        $this->connection->connectFTL();
        $socket = $this->connection->getSocket();

        if (!is_resource($socket)) {
            $data = [
                'FTLnotrunning' => true,
                'ERR'           => $this->connection->getCode(),
                'ERRMSG'        => $this->connection->getMessage()
            ];
        } else {
            $this->sendRequestFTL($call);
            $data = $this->getResponseFTL();
        }
        $this->connection->disconnectFTL();

        return $data;
    }


    public function sendRequestFTL($request)
    {
        $socket = $this->connection->getSocket();
        $request = '>' . $request;
        fwrite($socket, $request) or exit('{"error":"Could not send data to server"}');
    }


    public function getResponseFTL()
    {
        $response = [];
        $errCount = 0;
        $socket = $this->connection->getSocket();
        $out = fgets($socket);
        do {
            if ($out === '') {
                ++$errCount;
            } else {
                $response[] = rtrim($out);
            }

            if ($errCount > 100) {
                // Tried 100 times, but never got proper reply, fail to prevent busy loop
                exit('{"error":"Tried 100 times to connect to FTL server, but never got proper reply. Please check Port and logs!"}');
            }
            $out = fgets($socket);
            $eom = strrpos($out, '---EOM---') !== false;
        } while (!$eom);

        return $response;
    }

}