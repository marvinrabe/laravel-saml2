<?php

namespace Aacotroneo\Saml2\Events;

use Aacotroneo\Saml2\Auth;
use Aacotroneo\Saml2\User;

class Login
{

    protected $user;
    protected $auth;

    function __construct(User $user, Auth $auth)
    {
        $this->user = $user;
        $this->auth = $auth;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getAuth()
    {
        return $this->auth;
    }

}
