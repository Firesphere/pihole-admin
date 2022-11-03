<?php

namespace App\API\Gravity;

use App\API\APIBase;
use App\DB\SQLiteDB;
use App\Helper\Helper;
use DateTime;
use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Gravity extends APIBase
{
    /**
     * @param $raw
     * @return array|false[]|string
     * @throws Exception
     */
    public static function gravity_last_update($raw = false)
    {
        $db = new SQLiteDB('GRAVITYDB');
        $query = "SELECT value FROM info WHERE property = :property;";
        $result = $db->doQuery($query, [':property' => 'updated']);
        // Only fetch the first row. There shouldn't be any other anyway
        $date_file_created_unix = $result->fetchArray();
        if ($date_file_created_unix['value'] === false) {
            if ($raw) {
                return ['file_exists' => false];
            }

            return 'Gravity database not available';
        }
        // Destruct the SQLiteDB object
        $db = null;
        // Convert the UNIX timestamp to a Datetime and DateDiff
        $date_file_created = new DateTime('@' . $date_file_created_unix['value']);
        $date_now = new DateTime('now');
        $gravitydiff = date_diff($date_file_created, $date_now);
        if ($raw) {
            // Array output
            return [
                'file_exists' => true,
                'absolute'    => $date_file_created_unix['value'],
                'relative'    => [
                    'days'    => $gravitydiff->format('%a'),
                    'hours'   => $gravitydiff->format('%H'),
                    'minutes' => $gravitydiff->format('%I'),
                ],
            ];
        }

        switch ($days = $gravitydiff->d) {
            case $days > 1:
                $str = '%a days, ';
                break;
            case $days === 1:
                $str = 'one day, ';
                break;
            default:
                $str = '';
        }

        $str = sprintf('Adlists updated %s%%H:%%I (hh:mm) ago', $str);

        return $gravitydiff->format($str);
    }

    public function updateGravity(RequestInterface $request, ResponseInterface $response)
    {
        ob_end_flush();
        ini_set('output_buffering', '0');
        ob_implicit_flush(true);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $proc = popen('sudo pihole -g', 'r');
        while (!feof($proc)) {
            $this->printStream(fread($proc, 4096));
        }

        exit; // Hard exit
    }

    protected function printStream($datatext)
    {
        // Detect ${OVER} and replace it with something we can safely transmit
        $datatext = str_replace("\r[K", '<------', $datatext);
        $pos = strpos($datatext, '<------');
        // Detect if the ${OVER} line is within this line, e.g.
        // "Pending: String to replace${OVER}Done: String has been replaced"
        // If this is the case, we have to remove everything before ${OVER}
        // and return only the text thereafter
        if ($pos !== false && $pos !== 0) {
            $datatext = substr($datatext, $pos);
        }
        echo 'data: ' . implode("\ndata: ", explode("\n", $datatext)) . "\n\n";
    }

    public function searchGravity(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        ob_end_flush();
        ini_set('output_buffering', '0');
        ob_implicit_flush(true);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        if (!isset($params['domain'])) {
            $this->printStream('No domain provided');

            exit;
        }
        // Is this a valid domain?
        // Convert domain name to IDNA ASCII form for international domains
        $url = Helper::convertUnicodeToIDNA($params['domain']);
        if (!Helper::validDomain($url)) {
            $this->printStream(sprintf('%s is an invalid domain!', htmlentities($url)));

            exit;
        }
        $exact = '';
        if (isset($params['exact'])) {
            $exact = '-exact';
        } elseif (isset($params['bp'])) {
            $exact = '-bp';
        }

        $query = sprintf('sudo pihole -q -adlist %s %s', $url, $exact);

        $proc = popen($query, 'r');
        while (!feof($proc)) {
            $this->printStream(fread($proc, 4096));
        }

        exit;
    }
}
