<?php

namespace Sebdesign\VivaPayments\Test\Functional;

use Sebdesign\VivaPayments\OAuth;
use Sebdesign\VivaPayments\Test\TestCase;

class OAuthTest extends TestCase
{
    /**
     * @test
     * @group functional
     */
    public function getAccessToken()
    {
        $token = app(Oauth::class)->getAccessToken();

        $this->assertIsObject($token);
        $this->assertObjectHasAttribute('access_token', $token);
    }
}
