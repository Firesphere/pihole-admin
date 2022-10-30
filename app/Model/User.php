<?php

namespace App\Model;

use App\DB\SQLiteDB;

class User
{
    private static $table = 'users';

    private $user;

    /**
     * @param string $username
     * @return mixed
     */
    public function getUser(string $username)
    {
        $db = new SQLiteDB('USERDB', SQLITE3_OPEN_READONLY);
        $query = 'SELECT id, password FROM user WHERE username=:username';
        $params = [':username' => $username];
        $result = $db->doQuery($query, $params);

        $this->user = $result->fetchArray();

        return $this->user['id'];
    }

    public function createUser()
    {
    }

    /**
     * @param $id
     * @param $plaintextPassword
     * @return bool
     */
    public function validateUser($id, $plaintextPassword)
    {
        $password = $this->hashPassword($plaintextPassword);

        return hash_equals($password, $this->user['password']);
    }

    private function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
