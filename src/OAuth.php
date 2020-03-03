<?php

namespace Sebdesign\VivaPayments;

class OAuth
{
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

    /**
     * Get the access token.
     *
     * @return \stdClass
     */
    public function getAccessToken()
    {
        $this->client->useClientCredentials();

        return $this->client->post($this->getUrl(), [
            \GuzzleHttp\RequestOptions::FORM_PARAMS => [
                'grant_type' => 'client_credentials',
            ],
        ]);
    }

    protected function getUrl() : string
    {
        $environment = config('services.viva.environment');

        if ($environment === 'production') {
            return 'https://accounts.vivapayments.com/connect/token';
        }

        if ($environment === 'demo') {
            return 'https://demo-accounts.vivapayments.com/connect/token';
        }

        throw new \InvalidArgumentException('The Viva Payments environment must be demo or production.');
    }
}
