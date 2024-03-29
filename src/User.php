<?php

namespace MarvinRabe\LaravelSaml2;

use OneLogin\Saml2\Auth as OneLoginAuth;

class User
{
    public function __construct(protected OneLoginAuth $auth)
    {
    }

    /**
     * @return string Name Id retrieved from assertion processed this request
     */
    public function getNameId()
    {
        return $this->auth->getNameId();
    }

    /**
     * @return array attributes retrieved from assertion processed this request
     */
    public function getAttributes()
    {
        return $this->auth->getAttributes();
    }

    /**
     * Returns the requested SAML attribute
     * @param  string  $name  The requested attribute of the user.
     * @return array|null Requested SAML attribute ($name).
     */
    public function getAttribute($name)
    {
        return $this->auth->getAttribute($name);
    }

    /**
     * @return array attributes retrieved from assertion processed this request
     */
    public function getAttributesWithFriendlyName()
    {
        return $this->auth->getAttributesWithFriendlyName();
    }

    /**
     * @return string the saml assertion processed this request
     */
    public function getRawSamlAssertion()
    {
        return app('request')->input('SAMLResponse'); //just this request
    }

    public function getIntendedUrl()
    {
        $relayState = app('request')->input('RelayState'); //just this request

        $url = app('Illuminate\Contracts\Routing\UrlGenerator');

        if ($relayState && $url->full() != $relayState) {
            return $relayState;
        }
    }

    /**
     * Parses a SAML property and adds this property to this user or returns the value
     * @param  string  $samlAttribute
     * @param  string  $propertyName
     * @return array|null
     */
    public function parseUserAttribute($samlAttribute = null, $propertyName = null)
    {
        if (empty($samlAttribute)) {
            return null;
        }
        if (empty($propertyName)) {
            return $this->getAttribute($samlAttribute);
        }

        return $this->{$propertyName} = $this->getAttribute($samlAttribute);
    }

    /**
     * Parse the saml attributes and adds it to this user
     * @param  array  $attributes  Array of properties which need to be parsed, like this ['email' => 'urn:oid:0.9.2342.19200300.100.1.3']
     */
    public function parseAttributes($attributes = [])
    {
        foreach ($attributes as $propertyName => $samlAttribute) {
            $this->parseUserAttribute($samlAttribute, $propertyName);
        }
    }
}
