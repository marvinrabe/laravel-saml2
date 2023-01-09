<?php

namespace MarvinRabe\LaravelSaml2;

use MarvinRabe\LaravelSaml2\Events\Login;
use MarvinRabe\LaravelSaml2\Events\Logout;
use Illuminate\Support\Facades\Log;
use OneLogin\Saml2\Auth as OneLoginAuth;
use OneLogin\Saml2\Error as OneLoginError;
use RuntimeException;

class Auth
{
    public function __construct(
        protected OneLoginAuth $auth,
        protected ?Saml2UserProvider $provider = null
    ) {
    }

    /**
     * If a valid user was fetched from the saml assertion this request.
     */
    public function isAuthenticated(): bool
    {
        return $this->auth->isAuthenticated();
    }

    /**
     * The user info from the assertion
     */
    public function getSaml2User(): User
    {
        return new User($this->auth);
    }

    /**
     * The ID of the last message processed
     */
    public function getLastMessageId(): string
    {
        return $this->auth->getLastMessageId();
    }

    /**
     * Initiate a saml2 login flow. It will redirect! Before calling this, check if user is
     * authenticated (here in saml2). That would be true when the assertion was received this request.
     * @param  string|null  $returnTo  The target URL the user should be returned to after login.
     * @param  array  $parameters  Extra parameters to be added to the GET
     * @param  bool  $forceAuthn  When true the AuthNReuqest will set the ForceAuthn='true'
     * @param  bool  $isPassive  When true the AuthNReuqest will set the Ispassive='true'
     * @param  bool  $stay  True if we want to stay (returns the url string) False to redirect
     * @param  bool  $setNameIdPolicy  When true the AuthNReuqest will set a nameIdPolicy element
     * @return string|null If $stay is True, it return a string with the SLO URL + LogoutRequest + parameters
     * @throws \OneLogin\Saml2\Error
     */
    public function login(
        $returnTo = null,
        $parameters = [],
        $forceAuthn = false,
        $isPassive = false,
        $stay = false,
        $setNameIdPolicy = true
    ) {
        return $this->auth->login($returnTo, $parameters, $forceAuthn, $isPassive, $stay, $setNameIdPolicy);
    }

    /**
     * Initiate a saml2 logout flow. It will close session on all other SSO services. You should close
     * local session if applicable.
     * @param  string|null  $returnTo  The target URL the user should be returned to after logout.
     * @param  string|null  $nameId  The NameID that will be set in the LogoutRequest.
     * @param  string|null  $sessionIndex  The SessionIndex (taken from the SAML Response in the SSO process).
     * @param  string|null  $nameIdFormat  The NameID Format will be set in the LogoutRequest.
     * @param  bool  $stay  True if we want to stay (returns the url string) False to redirect
     * @param  string|null  $nameIdNameQualifier  The NameID NameQualifier will be set in the LogoutRequest.
     * @return string|null If $stay is True, it return a string with the SLO URL + LogoutRequest + parameters
     * @throws \OneLogin\Saml2\Error
     */
    public function logout(
        $returnTo = null,
        $nameId = null,
        $sessionIndex = null,
        $nameIdFormat = null,
        $stay = false,
        $nameIdNameQualifier = null
    ) {
        return $this->auth->logout($returnTo, [], $nameId, $sessionIndex, $stay, $nameIdFormat, $nameIdNameQualifier);
    }

    /**
     * Process a Saml response (assertion consumer service)
     */
    public function acs(): User
    {
        $this->auth->processResponse();

        $errors = $this->auth->getErrors();

        if (!empty($errors)) {
            throw new RuntimeException($this->auth->getLastErrorReason());
        }

        if (!$this->auth->isAuthenticated()) {
            throw new RuntimeException('Could not authenticate');
        }

        $user = $this->getSaml2User();
        Log::info('SSO User: '.$user->getNameId(), ['attributes' => $user->getAttributes()]);

        if ($this->provider === null) {
            throw new RuntimeException('No user provider configured');
        }

        $realUser = $this->provider->retrieveByNameId($user->getNameId());

        if ($realUser === null) {
            throw new RuntimeException('User with NameId '.$user->getNameId().' not found');
        }

        \Illuminate\Support\Facades\Auth::login(
            $realUser
        );

        event(new Login($user));

        return $user;
    }

    /**
     * Process a Saml response (assertion consumer service)
     * returns an array with errors if it can not logout
     */
    public function sls($retrieveParametersFromServer = false)
    {
        // destroy the local session by firing the Logout event
        $keep_local_session = false;
        $session_callback = function () {
            event(new Logout());
        };

        $this->auth->processSLO($keep_local_session, null, $retrieveParametersFromServer, $session_callback);

        $errors = $this->auth->getErrors();

        return $errors;
    }

    /**
     * Show metadata about the local sp. Use this to configure your saml2 IDP
     * @return mixed xml string representing metadata
     * @throws \InvalidArgumentException if metadata is not correctly set
     * @throws \OneLogin\Saml2\Error
     */
    public function getMetadata()
    {
        $settings = $this->auth->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (empty($errors)) {
            return $metadata;
        } else {
            throw new \InvalidArgumentException(
                'Invalid SP metadata: '.implode(', ', $errors),
                OneLoginError::METADATA_SP_INVALID
            );
        }
    }

    /**
     * Get the last error reason from \OneLogin\Saml2\Auth, useful for error debugging.
     * @return string|null
     * @see \OneLogin\Saml2\Auth::getLastErrorReason()
     */
    public function getLastErrorReason()
    {
        return $this->auth->getLastErrorReason();
    }
}
