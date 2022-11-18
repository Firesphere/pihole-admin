<?php

namespace App\Auth;

use App\Model\User;
use Psr\Container\ContainerInterface;
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
        /** @var \SlimSession\Helper $session */
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
        $user = (new User())->login($username, $password);

        $this->session->set('user', $user->getId());
        return true;
    }

    public function logout()
    {
        unset($_SESSION['user']);
        session_destroy();
    }
}
