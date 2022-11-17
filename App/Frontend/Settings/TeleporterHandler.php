<?php

namespace App\Frontend\Settings;

use App\DB\SQLiteDB;
use App\Frontend\Settings;
use App\PiHole;
use FilesystemIterator;
use Phar;
use PharData;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\UploadedFile;

class TeleporterHandler extends Settings
{
    /**
     * @var SQLiteDB
     */
    protected $gravity;

    /**
     * @var array
     */
    protected $flushedTables = [];

    protected static $valid_mimetypes = [
        'application/gzip',
        'application/tar',
        'application/x-compressed',
        'application/x-gzip'
    ];

    public function __construct(ContainerInterface $container)
    {
        if (!extension_loaded('Phar')) {
            exit('PHP Phar extension not loaded. Exiting ungracefully');
        }

        $this->gravity = new SQLiteDB('GRAVITY', SQLITE3_OPEN_READWRITE);
        parent::__construct($container);
    }

    public function teleport(RequestInterface $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        if (!isset($data['action'])) {
            $this->export($data);
        } else {
            $files = $request->getUploadedFiles();
            $this->import($data, $files);
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
            'whitelist.exact.json'        => $this->exportTable('domainlist', SQLiteDB::LISTTYPE_WHITELIST),
            'whitelist.regex.json'        => $this->exportTable('domainlist', SQLiteDB::LISTTYPE_REGEX_WHITELIST),
            'blacklist.exact.json'        => $this->exportTable('domainlist', SQLiteDB::LISTTYPE_BLACKLIST),
            'blacklist.regex.json'        => $this->exportTable('domainlist', SQLiteDB::LISTTYPE_REGEX_BLACKLIST),
            'adlist.json'                 => $this->exportTable('adlist'),
            'domain_audit.json'           => $this->exportTable('domain_audit'),
            'group.json'                  => $this->exportTable('group'),
            'client.json'                 => $this->exportTable('client'),
            'domainlist_by_group.json'    => $this->exportTable('domainlist_by_group'),
            'adlist_by_group.json'        => $this->exportTable('adlist_by_group'),
            'client_by_group.json'        => $this->exportTable('client_by_group'),
            '/etc/pihole/setupVars.conf'  => $this->exportFile('/etc/pihole/', 'setupVars.conf'),
            '/etc/pihole/dhcp.leases'     => $this->exportFile('/etc/pihole/', 'dhcp.leases'),
            '/etc/pihole/custom.list'     => $this->exportFile('/etc/pihole/', 'custom.list'),
            '/etc/pihole/pihole-FTL.conf' => $this->exportFile('/etc/pihole', 'pihole-FTL.conf'),
            '/etc/hosts'                  => $this->exportFile('/etc/', 'hosts'),
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
        foreach ($dataSets as $fileName => $data) {
            $archive->addFromString($fileName, $data);
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

    /**
     * @param $postData
     * @param array|UploadedFile[] $files
     * @return string|void
     * @throws \JsonException
     */
    public function import($postData, $files)
    {
        $file = $files['zip_file'];
        $filename = $file->getClientFilename();
        $source = $file->getFilePath();
        $mimeType = $file->getClientMediaType();

        $flush = $postData['flushtables'];
        // verify the file mime type

        $mime_valid = in_array($mimeType, self::$valid_mimetypes);

        // verify the file extension (Looking for ".tar.gz" at the end of the file name)
        $ext = array_slice(explode('.', $filename), -2, 2);
        $ext_valid = strtolower($ext[0]) === 'tar' && strtolower($ext[1]) === 'gz';

        if (!$ext_valid || !$mime_valid) {
            $error = sprintf(
                'The file you are trying to upload is not a .tar.gz file (filename: %s, type: %s). Please try again',
                htmlentities($filename),
                htmlentities($mimeType)
            );
            exit($error);
        }

        $fullFilePath = sys_get_temp_dir() . '/' . $filename;

        if (!move_uploaded_file($source, $fullFilePath)) {
            exit('Failed moving ' . htmlentities($source) . ' to ' . htmlentities($fullFilePath));
        }

        $archive = new PharData($fullFilePath);

        $importedsomething = false;
        $reloadsettingspage = false;


        $iterator = new \RecursiveIteratorIterator($archive, FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO);

        foreach ($iterator as $path => $file) {
            [$fileType, $fileDBName] = $this->getFileInfo($file);
            $count = 0;
            if (!$reloadsettingspage) {
                $reloadsettingspage = $fileDBName === '04-pihole-static-dhcp';
            }

            $fileContents = file_get_contents($file);
            switch ($fileType) {
                case 'json':
                    if ($fileDBName === 'auditlog') {
                        $fileDBName = 'domain_audit';
                    }
                    $fileContents = $this->getJSONContent($fileContents);
                    // Do the JSON thing
                    $count = $this->restoreTable($fileContents, $fileDBName, $flush);
                    $importedsomething = true;
                    break;
                case 'txt': // Note, txt files don't seem to exist anymore
                    $dataSet = array_filter(explode("\n", $fileContents));
                    // Return early if we cannot extract the lines in the file
                    if (!count($dataSet)) {
                        break;
                    }
                    if ($fileDBName === 'wildcardblocking') {
                        $fileDBName = 'blacklist.regex';
                    }
                    $count = $this->importTable($dataSet, $fileDBName, $flush, $fileDBName === 'wildcardblocking');
                    $importedsomething = true;
                    break;
                default:
                    if (empty($fileContents)) {
                        break;
                    }
                    $fileParts = explode($fullFilePath, $path);
                    $dataSet = array_filter(explode("\n", $fileContents));
                    if ($fileParts[1] !== '/etc/hosts') { // NEVER empty/overwrite /etc/hosts
                        $this->backupLocalFile($fileParts[1], $flush);
                        array_walk($dataSet, static function (&$item, $key) {
                            $item = substr($item, 0, 253) . "\n";
                        });
                    }
                    $newFile = @fopen($fileParts[1], 'ab');
                    if ($newFile !== false) {
                        foreach ($dataSet as $line) {
                            fwrite($newFile, $line);
                        }
                    } else {
                        echo sprintf('Could not restore file %s<br />', $fileParts[1]);
                    }


                    break;
            }
            echo sprintf("Processed %s, ran %d records<br />", $fileDBName, $count);
        }

        unlink($fullFilePath);

        if ($importedsomething) {
            PiHole::execute('restartdns');
        }
        if ($reloadsettingspage) {
            echo "<br>\n<span data-forcereload></span>";
        }
        echo "Import of data done";
        exit;
    }

    /**
     * Backup a local file that is to be overwritten/replaced
     * @param $file
     * @return void
     */
    private function backupLocalFile($file, $flush)
    {
        $localFile = @fopen($file, 'rb+');
        if ($localFile !== false) {
            $backup = sprintf('%s-%s.backup', $localFile, date('YmdHis'));
            copy($localFile, $backup);
            if ($flush) {
                ftruncate($localFile, 0);
            }
            fclose($localFile);
        }
    }

    /**
     * @param bool $flush
     * @param mixed $table
     * @return void
     */
    public function flushTable(mixed $table, bool $flush): void
    {
        if ($flush && !in_array($table, $this->flushedTables)) {
            $masterSelect = 'SELECT name FROM sqlite_master WHERE type=:tabletype AND name=:tablename';
            $result = $this->gravity->doQuery($masterSelect, [':tabletype' => 'table', ':tablename' => $table]);
            $tableExists = $result->fetchArray();
            if ($tableExists) {
                $this->gravity->doQuery(sprintf('DELETE FROM "%s";', $table));
                $this->flushedTables[] = $table;
            }
        }
    }

    /**
     * @param mixed $table
     * @param $queryList
     * @param array $dataSet
     * @param mixed $field
     * @param bool $wildcardstyle
     * @return int
     */
    public function insertRows(mixed $table, $queryList, array $dataSet, mixed $field, $wildcardstyle = false): int
    {
        $query = sprintf(
            'INSERT OR IGNORE INTO "%s" %s VALUES %s;',
            $table,
            $queryList['fields'],
            $queryList['values'],
        );

        $rowCount = 0;
        foreach ($dataSet as $row) {
            // Limit max length for a domain entry to 253 chars
            if ($field !== false && strlen((string)$row[$field]) > 253) {
                continue;
            }

            $queryParams = [];
            if ($wildcardstyle) {
                $row[$field] = '(\\.|^)' . str_replace('.', '\\.', $row[$field]) . '$';
            }
            foreach ($row as $key => $value) {
                $queryParams[':' . $key] = $value;
            }
            // Make sure it's set properly _after_ the data is put in the array.
            // And of course, if it's needed.
            if ($queryList['type'] >= 0) {
                $queryParams[':type'] = $queryList['type'];
            }
            $this->gravity->doQuery($query, $queryParams);

            ++$rowCount;
        }

        return $rowCount;
    }

    /**
     * Replacing archive_insert_into_table
     * @param $dataSet
     * @param $table
     * @param bool $flush
     * @param bool $wildcardstyle
     * @return int|void
     */
    protected function importTable($dataSet, $table, $flush = false, $wildcardstyle = false)
    {
        $listTypes = ImportQueryHelper::$listTypes;
        $queryList = ImportQueryHelper::$queryParts;
        $field = false;
        if (isset($listTypes[$table])) {
            $table = $listTypes[$table]['table'];
            // If the field "domain" is too long later on, we need to skip the dataset
            $queryList[$table]['type'] = $listTypes[$table]['type'];
            $field = 'domain';
        }

        $this->flushTable($table, $flush);

        // Add domains to requested table
        return $this->insertRows($table, $queryList[$table], $dataSet, $field, $wildcardstyle);
    }

    /**
     * Replacing archive_restore_table
     * @param array $dataSet
     * @param string $table
     * @param bool $flush
     * @return int
     */
    protected function restoreTable($dataSet, $table, $flush = false, $wildcardstyle = false)
    {
        $field = false;
        $listTypes = ImportQueryHelper::$listTypes;
        $queryList = ImportQueryHelper::$queryParts;
        if (isset($listTypes[$table])) {
            $originalTable = $table;
            $table = $listTypes[$table]['table'];
            $queryList[$table]['type'] = $listTypes[$originalTable]['type'];
            // If the field "domain" is too long later on, we need to skip the dataset
            $field = 'domain';
        }

        $this->flushTable($table, $flush);

        return $this->insertRows($table, $queryList[$table], $dataSet, $field, $wildcardstyle);
    }

    /**
     * @param string $table
     * @param int $type
     * @return false|string
     * @throws \JsonException
     */
    protected function exportTable($table, int $type = -1)
    {
        $queryStr = 'SELECT * FROM "%s"';
        if ($type >= 0) {
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

    /**
     * @param $file
     * @return false|array
     * @throws \JsonException
     */
    private function getJSONContent($contents)
    {
        // Return early if we cannot extract the JSON string
        if (is_null($contents)) {
            return false;
        }

        $contents = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        // Return early if we cannot decode the JSON string
        if (is_null($contents)) {
            return false;
        }

        return $contents;
    }

    /**
     * @param mixed $file
     * @return array
     */
    public function getFileInfo(mixed $file): array
    {
        $fileName = $file->getFilename();
        $fileParts = explode('.', $fileName);
        $fileType = end($fileParts);
        $fileDBName = str_replace('.' . $fileType, '', $fileName);

        return [$fileType, $fileDBName];
    }
}
