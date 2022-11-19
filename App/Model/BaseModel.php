<?php

namespace App\Model;

use App\DB\SQLiteDB;

class BaseModel
{
    protected $table;

    protected static $db;

    protected $id;

    public function __construct()
    {
        if (!self::$db) {
            self::$db = new SQLiteDB('USER', SQLITE3_OPEN_READWRITE);
        }
    }

    public function byId($class, $id)
    {
        /** @var User|Permission $class */
        $class = new $class();
        $query = sprintf("SELECT * FROM %s WHERE id = :id", $class->table);
        $result = self::$db->doQuery($query, [':id' => $id])->fetchArray();
        foreach ($result as $key => $value) {
            if (property_exists($class, $key)) {
                $class->$key = $value;
            }
        }

        return $class;
    }
}
