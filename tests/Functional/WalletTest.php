<?php

namespace Sebdesign\VivaPayments\Test\Functional;

use Sebdesign\VivaPayments\Test\TestCase;
use Sebdesign\VivaPayments\Wallet;

class WalletTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_the_wallets()
    {
        $this->markTestSkipped('Forbidden');

        dump($wallets = app(Wallet::class)->get());
    }
}
