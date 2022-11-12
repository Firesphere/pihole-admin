<?php

namespace App\Frontend\Settings;

use App\DB\SQLiteDB;
use App\Frontend\Settings;
use Phar;
use PharData;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TeleporterHandler extends Settings
{
    /**
     * @var SQLiteDB
     */
    protected $gravity;

    public function __construct(ContainerInterface $container)
    {
        $this->gravity = new SQLiteDB('GRAVITY', SQLITE3_OPEN_READONLY);
        parent::__construct($container);
    }

    public function teleport(RequestInterface $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        if (!isset($data['in'])) {
            $this->export($data);
        }
    }

    public function export($postData)
    {
        $hostname = str_replace('.', '_', gethostname());
        $tarname = sprintf('pi-hole-%s-teleporter_%s.tar.gz', $hostname, date('Y-m-d_H-i-s'));
        $archive_file_name = tempnam(sys_get_temp_dir(), 'pihole_teleporter_'); // create a random file name in the system's tmp dir for the intermediate archive
        unlink($archive_file_name); // remove intermediate file created by tempnam()
        $archive_file_name .= '.tar'; // Append ".tar" extension

        $archive = new PharData($archive_file_name);

        if ($archive->isWritable() !== true) {
            exit(sprintf("cannot open/create %s<br>PHP user:", htmlentities($archive_file_name)));
        }

        $dataSets = [
            ['whitelist.exact.json', $this->exportTable('domainlist', SQLiteDB::LISTTYPE_WHITELIST)],
            ['whitelist.regex.json', $this->exportTable('domainlist', SQLiteDB::LISTTYPE_REGEX_WHITELIST)],
            ['blacklist.exact.json', $this->exportTable('domainlist', SQLiteDB::LISTTYPE_BLACKLIST)],
            ['blacklist.regex.json', $this->exportTable('domainlist', SQLiteDB::LISTTYPE_REGEX_BLACKLIST)],
            ['adlist.json', $this->exportTable('adlist')],
            ['domain_audit.json', $this->exportTable('domain_audit')],
            ['group.json', $this->exportTable('group')],
            ['client.json', $this->exportTable('client')],
            ['domainlist_by_group.json', $this->exportTable('domainlist_by_group')],
            ['adlist_by_group.json', $this->exportTable('adlist_by_group')],
            ['client_by_group.json', $this->exportTable('client_by_group')],
            ['/etc/pihole/setupVars.conf', $this->exportFile('/etc/pihole/', 'setupVars.conf')],
            ['/etc/pihole/dhcp.leases', $this->exportFile('/etc/pihole/', 'dhcp.leases')],
            ['/etc/pihole/custom.list', $this->exportFile('/etc/pihole/', 'custom.list')],
            ['/etc/pihole/pihole-FTL.conf', $this->exportFile('/etc/pihole', 'pihole-FTL.conf')],
            ['etc/hosts', $this->exportFile('/etc/', 'hosts')],
        ];

        // Get the files for DNSMasq config
        if (file_exists('/etc/dnsmasq.d') && $dir = opendir('/etc/dnsmasq.d')) {
            while ($entry = readdir($dir)) {
                if ($entry !== '.' && $entry !== '..') {
                    $dataSets['etc/dnsmasq.d/' . $entry] = $this->exportFile('/etc/dnsmasq.d/', $entry);
                }
            }
            closedir($dir);
        }

        // Add all files to the archive
        // @todo use the built in addFromFile and addFromString method
        foreach ($dataSets as $set) {
            $archive->addFromString($set[0], $set[1]);
        }

        $archive->compress(Phar::GZ); // Creates a gzipped copy
        unlink($archive_file_name); // Unlink original tar file as it is not needed anymore
        $archive_file_name .= '.gz'; // Append ".gz" extension to ".tar"

        header('Content-type: application/gzip');
        header('Content-Transfer-Encoding: binary');
        header('Content-Disposition: attachment; filename=' . $tarname);
        header('Content-length: ' . filesize($archive_file_name));
        header('Pragma: no-cache');
        header('Expires: 0');
        if (ob_get_length() > 0) {
            ob_end_clean();
        }
        readfile($archive_file_name);
        ignore_user_abort(true);
        unlink($archive_file_name);

        exit;
    }

    public function import($postData, $config, &$success, &$error)
    {
    }

    /**
     * @param $table
     * @param $type
     * @return false|string
     * @throws \JsonException
     */
    protected function exportTable($table, $type = -1)
    {
        $queryStr = 'SELECT * FROM "%s"';
        if ($type > -1) {
            $queryStr .= sprintf(" WHERE type = '%s'", $type);
        }
        $query = sprintf($queryStr, $table);

        $results = $this->gravity->doQuery($query . ';');

        // Return early without creating a file if the
        // requested table cannot be accessed
        if (is_null($results)) {
            return json_encode([], JSON_THROW_ON_ERROR);
        }

        $content = [];
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $content[] = $row;
        }

        return json_encode($content, JSON_THROW_ON_ERROR);
    }

    /**
     * @param $path
     * @param $name
     * @return false|string
     */
    protected function exportFile($path, $name)
    {
        if (file_exists($path . $name)) {
            return file_get_contents($path . $name);
        }

        return '';
    }
}
