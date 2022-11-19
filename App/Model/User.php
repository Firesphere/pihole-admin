<?php

namespace App\Model;

use App\Helper\Config;

/**
 * A basic user class. A user can have a name, and a password.
 * Permissions are what a user can access, which is checked in Permission::check()
 * A user can have a password set
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
    protected $password;

    protected $permissions = [];

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
    private function setPassword($password)
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
            $this->permissions[] = $this->byId($permissionId['id'], Permission::class);
        }

        return $this->permissions;
    }
}
