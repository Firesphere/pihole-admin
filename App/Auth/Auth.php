<?php

namespace App\Auth;

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
        if ($id = $this->check()) {
            return (new User())->byId($id);
        }

        return null;
    }

    public function check()
    {
        return $this->session->get('user') !== null;
    }

    public function login($username, $password)
    {
        if ($user = (new User())->login($username, $password)) {
            $this->session->set('user', $user->getId());

            return $user;
        }

        return false;
    }

    public function logout()
    {
        $this->session->delete('user');
        $this->session->clear();
        (new Session());
    }
}
