<?php

namespace App\Model;

use App\DB\SQLiteDB;

class User
{
    /**
     * @var SQLiteDB
     */
    private $db;

    /**
     * @var int
     */
    protected $id = 0;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    private $password;

    public function __construct()
    {
        $this->db = new SQLiteDB('USER', SQLITE3_OPEN_READWRITE);
    }

    public function setPassword($password)
    {
        $this->db->doQuery(
            "UPDATE users SET password = :password",
            [':password' => password_hash($password, PASSWORD_DEFAULT)]
        );
    }

    /**
     * @param string $username
     * @return self
     */
    public function getUser(string $username)
    {
        $db = new SQLiteDB('USER', SQLITE3_OPEN_READONLY);
        $query = 'SELECT id, username, password FROM user WHERE username=:username';
        $params = [':username' => $username];
        $result = $db->doQuery($query, $params);

        $arrayData = $result->fetchArray();
        $this->id = $arrayData['id'];
        $this->username = $arrayData['username'];
        $this->password = $arrayData['password'];

        return $this;
    }

    public function createUser()
    {
    }

    /**
     * @param $id
     * @param $enteredPassword
     * @return bool
     */
    public function validatePassword($enteredPassword)
    {
        $password = password_hash($enteredPassword, PASSWORD_DEFAULT);

        return password_verify($password, $this->password);
    }

    public function byId($id)
    {
        if ($_SESSION['user'] === $id) {
            $dbUser = $this->db->doQuery('SELECT id, username FROM user WHERE id = :id', [':id' => $id])->fetchArray();
            $this->username = $dbUser['username'];
            $this->id = $dbUser['id'];
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    private function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
