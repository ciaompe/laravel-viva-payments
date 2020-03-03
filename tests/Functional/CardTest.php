<?php

namespace Sebdesign\VivaPayments\Test\Functional;

use Illuminate\Support\Carbon;
use Sebdesign\VivaPayments\Card;
use Sebdesign\VivaPayments\Test\TestCase;

class CardTest extends TestCase
{
    /**
     * @test
     * @group functional
     * @return string
     */
    public function it_creates_a_card_token()
    {
        // arrange

        $expirationDate = Carbon::parse('next year');

        // act

        $cardToken = app(Card::class)->cardToken('Customer name', '4111 1111 1111 1111', 111, $expirationDate->month, $expirationDate->year);

        // assert

        $this->assertIsString($cardToken);

        return $cardToken;
    }

    /**
     * @test
     * @group functional
     * @return string
     */
    public function it_creates_a_charge_token()
    {
        // arrange

        $expirationDate = Carbon::parse('next year');

        // act

        $chargeToken = app(Card::class)->chargeToken(
            'Customer name',
            '4111 1111 1111 1111',
            111,
            $expirationDate->month,
            $expirationDate->year
        );

        // assert

        $this->assertIsString($chargeToken);

        return $chargeToken;
    }

    /**
     * @test
     * @group functional
     * @return string
     */
    public function it_creates_a_native_charge_token()
    {
        // arrange

        $expirationDate = Carbon::parse('next year');

        // act

        $chargeToken = app(Card::class)->nativeChargeToken(
            'Customer name',
            '4111 1111 1111 1111',
            111,
            $expirationDate->month,
            $expirationDate->year,
            1500,
            0,
            true,
            'https://www.example.com'
        );
dd($chargeToken);
        // assert

        $this->assertIsString($chargeToken);

        return $chargeToken;
    }

    /**
     * @test
     * @group functional
     * @depends it_creates_a_charge_token
     */
    public function it_gets_a_card_token_using_a_charge_token(string $chargeToken)
    {
        // act

        $cardToken = app(Card::class)->getCardToken($chargeToken);

        // assert

        $this->assertIsString($cardToken);
    }

    /**
     * @test
     * @group functional
     * @depends it_creates_a_card_token
     */
    public function it_gets_a_charge_token_using_a_card_token(string $cardToken)
    {
        // act

        $chargeToken = app(Card::class)->getChargeToken($cardToken);

        // assert

        $this->assertIsString($chargeToken);
    }

    /**
     * @test
     * @group functional
     */
    public function it_checks_for_installments()
    {
        $installments = app(Card::class)->installments('4111 1111 1111 1111');

        $this->assertIsInt($installments);
    }
}
