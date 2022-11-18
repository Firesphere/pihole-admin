<?php

namespace App\Migrate;

require 'vendor/autoload.php';

use App\DB\SQLiteDB;
use App\Helper\Config;

class Migrate
{
    /**
     * @var array
     */
    private $migrations = [];
    private $records;

    public function __construct($args)
    {
        new Config();
        $dir = opendir(__DIR__ . '/Migrations/' . $args[1]);
        while ($file = readdir($dir)) {
            if (!in_array($file, ['record.txt', '.', '..'])) {
                $this->migrations[] = $file;
            }
        }
        $recordFile = sprintf('%s/Migrations/%s/record.txt', __DIR__, $args[1]);
        $this->records = fopen($recordFile, 'ab+');
        if ($args[1] === 'up') {
            $tmp = array_flip($this->migrations);
            while ($record = fgets($this->records)) {
                unset($tmp[$record]);
            }
            $this->migrations = array_flip($tmp);
            asort($this->migrations);
            $this->up();
        }
//        if ($args[1] === 'down') {
//            $tmp = [];
//            while ($record = fgets($this->records)) {
//                $tmp[] = $record;
//            }
//            $this->migrations = array_reverse($tmp);
//
//            $this->down();
//        }
        fclose($this->records);
    }

    public function up()
    {
        foreach ($this->migrations as $file) {
            $migrations = fopen(__DIR__ . '/Migrations/up/' . $file, 'rb');
            while ($migration = fgets($migrations)) {
                [$table, $query] = explode(':', $migration);
                (new SQLiteDB($table, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE))->doQuery($query);
                echo sprintf("- Executed query\n%s\n on table %s\n", $query, $table);
            }
            fwrite($this->records, $file . "\n");
            fclose($migrations);
        }
    }
}

(new Migrate($argv));
