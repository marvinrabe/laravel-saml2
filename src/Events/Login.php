<?php

namespace MarvinRabe\LaravelSaml2\Events;

use MarvinRabe\LaravelSaml2\User;

class Login
{
    public function __construct(protected User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
