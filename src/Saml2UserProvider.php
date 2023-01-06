<?php

namespace MarvinRabe\LaravelSaml2;

use Illuminate\Contracts\Auth\Authenticatable;

interface Saml2UserProvider
{

    public function retrieveByNameId(string $nameId): Authenticatable|null;

}
