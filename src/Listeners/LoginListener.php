<?php

namespace Aacotroneo\Saml2\Listeners;

use Aacotroneo\Saml2\Blacklist;
use Aacotroneo\Saml2\Events\Login;
use Aacotroneo\Saml2\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

abstract class LoginListener
{

    protected $blacklist;

    public function __construct(Blacklist $blacklist)
    {
        $this->blacklist = $blacklist;
    }

    abstract protected function findUser(User $user);

    public function handle(Login $event)
    {
        $messageId = $event->getAuth()->getLastMessageId();

        if ($this->blacklist->has($messageId)) {
            Log::info('SSO Failed: Message ID blacklisted' . $messageId);
            return;
        }

        $this->blacklist->add($messageId);

        Log::info('SSO: ' . $event->getUser()->getUserId());

        Auth::login($this->findUser($event->getUser()));
    }

}
