# Laravel SAML 2

[![Latest Version on Packagist](https://img.shields.io/packagist/v/marvinrabe/laravel-saml2.svg?style=flat-square)](https://packagist.org/packages/marvinrabe/laravel-saml2)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/marvinrabe/laravel-saml2/run-tests?label=tests)](https://github.com/marvinrabe/laravel-saml2/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/marvinrabe/laravel-saml2.svg?style=flat-square)](https://packagist.org/packages/marvinrabe/laravel-saml2)

Saml2 integration as a SP (service provider) for Laravel based on OneLogin toolkit.

## Installation

You can install the package via composer:

```bash
composer require marvinrabe/laravel-saml2
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Aacotroneo\Saml2\Saml2ServiceProvider"
```

This config is almost identical to that of [OneLogin](https://github.com/onelogin/php-saml). There are also some other configurations about routes you may want to check, they are pretty straightforward.

### Configuration

Once you publish `config/saml2.php` to your own files, you need to configure your SP and IdP (remote server). The only real difference between this config and the one that OneLogin uses, is that the SP entityId, assertionConsumerService url and singleLogoutService URL are injected by the library. They are taken from routes 'saml_metadata', 'saml_acs' and 'saml_sls' respectively.

Remember that you don't need to implement those routes, but you'll need to add them to your IdP configuration. For example, if you use simplesamlphp, add the following to /metadata/sp-remote.php

```php
$metadata['http://laravel_url/saml2/metadata'] = array(
    'AssertionConsumerService' => 'http://laravel_url/saml2/acs',
    'SingleLogoutService' => 'http://laravel_url/saml2/sls',
    //the following two affect what the $Saml2user->getUserId() will return
    'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
    'simplesaml.nameidattribute' => 'uid' 
);
```

You can check the metadata if you actually navigate to 'http://yourdomain.test/saml2/metadata'

## Usage


When you want your user to login, just call `Saml2Auth::login()` or redirect to route 'saml2_login'. Just remember that it does not use any session storage, so if you ask it to login it will redirect to the IDP whether the user is logged in or not. For example, you can change your authentication middleware.
```php
	public function handle($request, Closure $next)
	{
		if ($this->auth->guest())
		{
			if ($request->ajax())
			{
				return response('Unauthorized.', 401);
			}
			else
			{
        			 return Saml2::login(URL::full());
                		 //return redirect()->guest('auth/login');
			}
		}

		return $next($request);
	}
```

Since Laravel 5.3, you can change your unauthenticated method in ```app/Exceptions/Handler.php```.
```php
protected function unauthenticated($request, AuthenticationException $exception)
{
	if ($request->expectsJson())
        {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return Saml2Auth::login();
}
```

The Saml2::login will redirect the user to the IDP and will came back to an endpoint the library serves at /saml2/acs. That will process the response and fire an event when ready. The next step for you is to handle that event. You just need to login the user or refuse.

```php

 Event::listen('Aacotroneo\Saml2\Events\Saml2LoginEvent', function (Saml2LoginEvent $event) {
            $messageId = $event->getSaml2Auth()->getLastMessageId();
            // your own code preventing reuse of a $messageId to stop replay attacks
            $user = $event->getSaml2User();
            $userData = [
                'id' => $user->getUserId(),
                'attributes' => $user->getAttributes(),
                'assertion' => $user->getRawSamlAssertion()
            ];
             $laravelUser = //find user by ID or attribute
             //if it does not exist create it and go on  or show an error message
             Auth::login($laravelUser);
        });

```
### Auth persistence

Becarefull about necessary Laravel middleware for Auth persistence in Session.

For exemple, it can be:

```
# in App\Http\Kernel
protected $middlewareGroups = [
        'web' => [
	    ...
	],
	'api' => [
            ...
        ],
        'saml' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ],

```

And in `config/saml2.php` :
```
    /**
     * which middleware group to use for the saml routes
     * Laravel 5.2 will need a group which includes StartSession
     */
    'routesMiddleware' => ['saml'],
```

### Log out
Now there are two ways the user can log out.
 + 1 - By logging out in your app: In this case you 'should' notify the IDP first so it closes global session.
 + 2 - By logging out of the global SSO Session. In this case the IDP will notify you on /saml2/slo endpoint (already provided)

For case 1 call `Saml2Auth::logout();` or redirect the user to the route 'saml_logout' which does just that. Do not close the session inmediately as you need to receive a response confirmation from the IDP (redirection). That response will be handled by the library at /saml2/sls and will fire an event for you to complete the operation.

For case 2 you will only receive the event. Both cases 1 and 2 receive the same event. 

Note that for case 2, you may have to manually save your session to make the logout stick (as the session is saved by middleware, but the OneLogin library will redirect back to your IDP before that happens)

```php
        Event::listen('Aacotroneo\Saml2\Events\Saml2LogoutEvent', function ($event) {
            Auth::logout();
            Session::save();
        });
```

## Testing

``` bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
