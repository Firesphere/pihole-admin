<?php

namespace App\Auth;

use App\Model\User;

class Auth
{
    public function user()
    {
        if ($this->check()) {
            return (new User())->byId($_SESSION['id']);
        }

        return null;
    }

    public function check()
    {
        return isset($_SESSION['user']);
    }

    public function login($username, $password)
    {
        $user = (new User())->getUser($username);

        if (!$user->validatePassword($password)) {
            return false;
        }

        $_SESSION['user'] = $user->getId();

        return true;
    }

    public function logout()
    {
        unset($_SESSION['user']);
        session_destroy();
    }
}
