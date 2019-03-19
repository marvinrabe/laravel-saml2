<?php

namespace Aacotroneo\Saml2\Listeners;

use Aacotroneo\Saml2\Events\Login;
use Aacotroneo\Saml2\User;
use Illuminate\Support\Facades\Auth;

abstract class LoginListener
{

    abstract protected function findUser(User $user);

    public function handle(Login $event)
    {
        $messageId = $event->getAuth()->getLastMessageId();

        // TODO: your own code preventing reuse of a $messageId to stop replay attacks

        Auth::login($this->findUser($event->getUser()));
    }

}
