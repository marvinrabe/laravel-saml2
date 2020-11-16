<?php

namespace MarvinRabe\LaravelSAML2\Tests;

use Aacotroneo\Saml2\Auth;
use Mockery as m;

class Saml2AuthTest extends TestCase
{
    public function testIsAuthenticated()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);

        $auth->shouldReceive('isAuthenticated')->andReturn('return');

        $this->assertEquals('return', $saml2->isAuthenticated());
    }

    public function testLogin()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);
        $auth->shouldReceive('login')->once();
        $saml2->login();
    }

    public function testLogout()
    {
        $expectedReturnTo = 'http://localhost';
        $expectedSessionIndex = 'session_index_value';
        $expectedNameId = 'name_id_value';
        $expectedNameIdFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
        $expectedStay = true;
        $expectedNameIdNameQualifier = 'name_id_name_qualifier';
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);
        $auth->shouldReceive('logout')
            ->with(
                $expectedReturnTo,
                [],
                $expectedNameId,
                $expectedSessionIndex,
                $expectedStay,
                $expectedNameIdFormat,
                $expectedNameIdNameQualifier
            )
            ->once();
        $saml2->logout(
            $expectedReturnTo,
            $expectedNameId,
            $expectedSessionIndex,
            $expectedNameIdFormat,
            $expectedStay,
            $expectedNameIdNameQualifier
        );
    }

    public function testAcsError()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);
        $auth->shouldReceive('processResponse')->once();
        $auth->shouldReceive('getErrors')->once()->andReturn(['errors']);

        $error = $saml2->acs();

        $this->assertNotEmpty($error);
    }

    public function testAcsNotAutenticated()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);
        $auth->shouldReceive('processResponse')->once();
        $auth->shouldReceive('getErrors')->once()->andReturn(null);
        $auth->shouldReceive('isAuthenticated')->once()->andReturn(false);
        $error = $saml2->acs();

        $this->assertNotEmpty($error);
    }

    public function testAcsOK()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);
        $auth->shouldReceive('processResponse')->once();
        $auth->shouldReceive('getErrors')->once()->andReturn(null);
        $auth->shouldReceive('isAuthenticated')->once()->andReturn(true);

        $error = $saml2->acs();

        $this->assertEmpty($error);
    }

    public function testSlsError()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);
        $auth->shouldReceive('processSLO')->once();
        $auth->shouldReceive('getErrors')->once()->andReturn('errors');

        $error = $saml2->sls();

        $this->assertNotEmpty($error);
    }

    public function testSlsOK()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);
        $auth->shouldReceive('processSLO')->once();
        $auth->shouldReceive('getErrors')->once()->andReturn(null);

        $error = $saml2->sls();

        $this->assertEmpty($error);
    }

    public function testCanGetLastError()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);

        $auth->shouldReceive('getLastErrorReason')->andReturn('lastError');

        $this->assertSame('lastError', $saml2->getLastErrorReason());
    }

    public function testGetUserAttribute()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);

        $user = $saml2->getSaml2User();

        $auth->shouldReceive('getAttribute')
            ->with('urn:oid:0.9.2342.19200300.100.1.3')
            ->andReturn(['test@example.com']);

        $this->assertEquals(['test@example.com'], $user->getAttribute('urn:oid:0.9.2342.19200300.100.1.3'));
    }

    public function testParseSingleUserAttribute()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);

        $user = $saml2->getSaml2User();

        $auth->shouldReceive('getAttribute')
            ->with('urn:oid:0.9.2342.19200300.100.1.3')
            ->andReturn(['test@example.com']);

        $user->parseUserAttribute('urn:oid:0.9.2342.19200300.100.1.3', 'email');

        $this->assertEquals($user->email, ['test@example.com']);
    }

    public function testParseMultipleUserAttributes()
    {
        $auth = m::mock('OneLogin\Saml2\Auth');
        $saml2 = new Auth($auth);

        $user = $saml2->getSaml2User();

        $auth->shouldReceive('getAttribute')
            ->twice()
            ->andReturn(['test@example.com'], ['Test User']);

        $user->parseAttributes([
            'email' => 'urn:oid:0.9.2342.19200300.100.1.3',
            'displayName' => 'urn:oid:2.16.840.1.113730.3.1.241',
        ]);

        $this->assertEquals($user->email, ['test@example.com']);
        $this->assertEquals($user->displayName, ['Test User']);
    }
}
