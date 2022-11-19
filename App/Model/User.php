<?php

namespace App\Model;

use App\DB\SQLiteDB;
use App\Helper\Config;

/**
 *
 */
class User extends BaseModel
{
    protected $table = 'user';
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    private $password;

    protected $permissions = [];

    /**
     * @param string $username
     * @param string $password
     * @return self
     */
    public function login(string $username, string $password)
    {
        $query = 'SELECT id, username, password FROM user WHERE username=:username';
        $params = [':username' => $username];
        $result = self::$db->doQuery($query, $params)->fetchArray();

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
        self::$db->doQuery(
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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getPermissions($refresh = false)
    {
        if (!$refresh && count($this->permissions)) {
            return $this->permissions;
        }
        $relationQuery = "SELECT permission_id FROM user_permissions
                WHERE user_permissions.user_id = :id;";
        $result = self::$db->doQuery($relationQuery, [':id' => $this->id]);

        while ($permissionId = $result->fetchArray()) {
            $this->permissions[] = $this->byId(Permission::class, $permissionId['id']);
        }

        return $this->permissions;
    }
}
