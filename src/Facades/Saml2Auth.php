<?php

namespace MarvinRabe\LaravelSaml2\Facades;

use Illuminate\Support\Facades\Facade;
use MarvinRabe\LaravelSaml2\Auth;

class Saml2Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Auth::class;
    }
}
