<?php

namespace Aacotroneo\Saml2\Listeners;

use Aacotroneo\Saml2\Events\Login;
use Aacotroneo\Saml2\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

abstract class LoginListener
{
    abstract protected function findUser(User $user);

    public function handle(Login $event)
    {
        Log::info('SSO: ' . $event->getUser()->getUserId(), ['attributes' => $event->getUser()->getAttributes()]);

        Auth::login($this->findUser($event->getUser()));
    }
}
