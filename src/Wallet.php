<?php

namespace Sebdesign\VivaPayments;

class Wallet
{
    const ENDPOINT = 'https://uat-api.vivapayments.com/walletaccounts/v1/';

    /**
     * @var \Sebdesign\VivaPayments\Client
     */
    protected $client;

    /**
     * Constructor.
     *
     * @param \Sebdesign\VivaPayments\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function get()
    {
        $this->client->useOAuth2Authentication();

        return $this->client->get(self::ENDPOINT.'wallets/');
    }
}
