<?php

namespace App\Auth;

use App\DB\SQLiteDB;
use App\Model\User;
use Slim\Middleware\Session;
use SlimSession\Helper;

class Auth
{
    /**
     * @var Session
     */
    protected $session;

    public function __construct()
    {
        /** @var Helper $session */
        $this->session = new Helper();
    }

    public function user()
    {
        if ($id = $this->session->get('user')) {
            return (new User())->byId($id);
        }

        return null;
    }

    public function check($id)
    {
        return $this->session->get('user') === $id;
    }

    public function login($username, $password)
    {
        $db = new SQLiteDB('USERS', SQLITE3_OPEN_READONLY);
        $query = 'SELECT id, username, password FROM user WHERE username=:username';
        $params = [':username' => $username];
        $result = $db->doQuery($query, $params)->fetchArray();

        if ($result !== false) {
            $user = new User();
            $user->set('id', $result['id']);
            $user->set('username', $result['username']);
            $user->set('password', $result['password']);
            if ($user->validatePassword($password, $username === 'admin')) {
                $this->session->set('user', $user->getId());

                return $user;
            }
        }

        $this->logout();

        return false;
    }

    public function logout()
    {
        $this->session->delete('user');
        $this->session->clear();
    }
}
