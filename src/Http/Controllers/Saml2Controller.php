<?php

namespace Aacotroneo\Saml2\Http\Controllers;

use Aacotroneo\Saml2\Auth;
use Aacotroneo\Saml2\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class Saml2Controller extends Controller
{
    protected $saml2Auth;

    /**
     * @param Auth $saml2Auth injected.
     */
    public function __construct(Auth $saml2Auth)
    {
        $this->saml2Auth = $saml2Auth;
    }

    public function metadata()
    {
        $metadata = $this->saml2Auth->getMetadata();

        return response($metadata, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Process an incoming saml2 assertion request.
     * Fires 'Saml2LoginEvent' event if a valid user is Found
     */
    public function acs()
    {
        try {
            $errors = $this->saml2Auth->acs();

            if (! empty($errors)) {
                throw new \RuntimeException($this->saml2Auth->getLastErrorReason());
            }

            $user = $this->saml2Auth->getSaml2User();

            event(new Login($user, $this->saml2Auth));

            $redirectUrl = $user->getIntendedUrl();

            if ($redirectUrl !== null) {
                return redirect($redirectUrl);
            }

            return redirect(config('saml2.loginRoute'));
        } catch (\Exception $e) {
            Log::error('SSO failed: ' . $e->getMessage());

            return redirect(config('saml2.errorRoute'));
        }
    }

    /**
     * Process an incoming saml2 logout request.
     * Fires 'Saml2LogoutEvent' event if its valid.
     * This means the user logged out of the SSO infrastructure, you 'should' log him out locally too.
     */
    public function sls()
    {
        $error = $this->saml2Auth->sls(config('saml2.retrieveParametersFromServer'));

        if (! empty($error)) {
            Log::error('SLO failed.', ['errors' => $error]);
        }

        return redirect(config('saml2.logoutRoute')); //may be set a configurable default
    }

    /**
     * This initiates a logout request across all the SSO infrastructure.
     */
    public function logout(Request $request)
    {
        $returnTo = $request->query('returnTo');
        $sessionIndex = $request->query('sessionIndex');
        $nameId = $request->query('nameId');
        $this->saml2Auth->logout($returnTo, $nameId, $sessionIndex); //will actually end up in the sls endpoint
    }

    /**
     * This initiates a login request
     */
    public function login()
    {
        $this->saml2Auth->login(config('saml2.loginRoute'));
    }
}
