<?php

namespace App\Model;

use App\DB\SQLiteDB;
use App\Helper\Config;

/**
 *
 */
class User
{
    /**
     * @var int
     */
    protected $id = 0;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var SQLiteDB
     */
    private $db;
    /**
     * @var string
     */
    private $password;

    /**
     *
     */
    public function __construct()
    {
        $this->db = new SQLiteDB('USER', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    }

    /**
     * @param string $username
     * @param string $password
     * @return self
     */
    public function login(string $username, string $password)
    {
        $query = 'SELECT id, username, password FROM user WHERE username=:username';
        $params = [':username' => $username];
        $result = $this->db->doQuery($query, $params)->fetchArray();

        if ($result !== false) {
            $this->id = $result['id'];
            $this->username = $result['username'];
            $this->password = $result['password'];
        }

        return $this->validatePassword($password, $username === 'admin');
    }

    /**
     * @param $enteredPassword
     * @param bool $isAdmin
     * @return bool|self
     */
    public function validatePassword($enteredPassword, $isAdmin = false)
    {
        $valid = password_verify((string)$enteredPassword, (string)$this->password);
        if ($isAdmin && !$valid) {
            $oldHash = hash('sha256', hash('sha256', $enteredPassword));
            $oldPassword = Config::get('pihole.WEBPASSWORD');
            if (hash_equals($oldPassword, $oldHash)) {
                $this->setPassword($enteredPassword);

                return $this;
            }
        }

        if ($valid) {
            return $this;
        }

        return false;
    }

    /**
     * @param $password
     * @return void
     */
    public function setPassword($password)
    {
        $this->db->doQuery(
            "UPDATE user SET password = :password WHERE id = :id",
            [
                ':password' => $this->hashPassword($password),
                ':id'       => $this->id
            ]
        );
    }

    /**
     * @param $password
     * @return string
     */
    private function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * @return void
     */
    public function createUser()
    {
    }

    /**
     * @param $id
     * @return $this
     */
    public function byId($id)
    {
        $dbUser = $this->db->doQuery('SELECT id, username FROM user WHERE id = :id', [':id' => $id])->fetchArray();
        if (count($dbUser)) {
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
}
