<?php

namespace Sebdesign\VivaPayments\Test\Unit;

use Sebdesign\VivaPayments\Card;
use Sebdesign\VivaPayments\Test\TestCase;

class CardTest extends TestCase
{
    /**
     * @test
     * @group unit
     */
    public function it_creates_a_card_token()
    {
        $this->mockJsonResponses([['Token' => 'foo']]);
        $this->mockRequests();

        $card = new Card($this->client);

        $token = $card->cardToken('Customer name', '4111 1111 1111 1111', 111, 06, 2016);
        $request = $this->getLastRequest();

        $this->assertIsString($token);
        $this->assertBasicAuthentication($request);
        $this->assertEquals('foo', $token, 'The token should be foo');
        $this->assertMethod('POST', $request);
        $this->assertQuery('key', $this->app['config']->get('services.viva.public_key'), $request);
        $this->assertBody('CardHolderName', 'Customer name', $request);
        $this->assertBody('Number', '4111111111111111', $request);
        $this->assertBody('CVC', '111', $request);
        $this->assertBody('ExpirationDate', '2016-06-15', $request);
    }

    /**
     * @test
     * @group unit
     */
    public function it_gets_a_card_token_using_a_charge_token()
    {
        $this->mockJsonResponses([['token' => 'bar']]);
        $this->mockRequests();

        $this->client->setAccessToken('baz');

        $card = new Card($this->client);

        $cardToken = $card->getCardToken('foo');
        $request = $this->getLastRequest();

        $this->assertIsString($cardToken);
        $this->assertHeader('Authorization', 'Bearer baz', $request);
        $this->assertEquals('bar', $cardToken, 'The card token should be bar');
        $this->assertMethod('GET', $request);
        $this->assertQuery('chargeToken', 'foo', $request);
    }

    /**
     * @test
     * @group unit
     */
    public function it_gets_a_charge_token_using_a_card_token()
    {
        $this->mockJsonResponses([['chargeToken' => 'bar']]);
        $this->mockRequests();

        $this->client->setAccessToken('baz');

        $card = new Card($this->client);

        $chargeToken = $card->getChargeToken('foo');
        $request = $this->getLastRequest();

        $this->assertIsString($chargeToken);
        $this->assertHeader('Authorization', 'Bearer baz', $request);
        $this->assertEquals('bar', $chargeToken, 'The charge token should be bar');
        $this->assertMethod('GET', $request);
        $this->assertQuery('token', 'foo', $request);
    }

    /**
     * @test
     * @group unit
     */
    public function it_checks_for_installments()
    {
        $this->mockJsonResponses([['MaxInstallments' => 36]]);
        $this->mockRequests();

        $card = new Card($this->client);

        $installments = $card->installments('4111 1111 1111 1111');
        $request = $this->getLastRequest();

        $this->assertMethod('GET', $request);
        $this->assertBasicAuthentication($request);
        $this->assertHeader('CardNumber', 4111111111111111, $request);
        $this->assertEquals(36, $installments, 'The installments should be 36.');
    }
}
