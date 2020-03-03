<?php

namespace Sebdesign\VivaPayments;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Client
{
    /**
     * Demo environment URL.
     */
    const DEMO_URL = 'https://demo.vivapayments.com';

    /**
     * Production environment URL.
     */
    const PRODUCTION_URL = 'https://www.vivapayments.com';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * Constructor.
     *
     * @param  \GuzzleHttp\Client   $client
     * @return void
     */
    public function __construct(GuzzleClient $client)
    {
        $this->client = $client;
    }

    /**
     * Make a GET request.
     *
     * @param  string $url
     * @param  array  $options
     * @return \stdClass
     */
    public function get(string $url, array $options = [])
    {
        $response = $this->client->get($url, array_merge_recursive($this->defaultOptions, $options));

        return $this->getBody($response);
    }

    /**
     * Make a POST request.
     *
     * @param  string $url
     * @param  array  $options
     * @return \stdClass
     */
    public function post(string $url, array $options = [])
    {
        $response = $this->client->post($url, array_merge_recursive($this->defaultOptions, $options));

        return $this->getBody($response);
    }

    /**
     * Make a PATCH request.
     *
     * @param  string $url
     * @param  array  $options
     * @return \stdClass
     */
    public function patch(string $url, array $options = [])
    {
        $response = $this->client->patch($url, array_merge_recursive($this->defaultOptions, $options));

        return $this->getBody($response);
    }

    /**
     * Make a DELETE request.
     *
     * @param  string $url
     * @param  array  $options
     * @return \stdClass
     */
    public function delete(string $url, array $options = [])
    {
        $response = $this->client->delete($url, array_merge_recursive($this->defaultOptions, $options));

        return $this->getBody($response);
    }

    public function useBasicAuthentication() : void
    {
        $this->defaultOptions = [
            \GuzzleHttp\RequestOptions::AUTH => [
                config('services.viva.merchant_id'),
                config('services.viva.api_key'),
            ],
        ];
    }

    public function useOAuth2Authentication() : void
    {
        $accessToken = $this->getAccessToken();

        $this->defaultOptions = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Authorization' => sprintf("Bearer %s", $accessToken),
            ],
        ];
    }

    public function useClientCredentials() : void
    {
        $this->defaultOptions = [
            \GuzzleHttp\RequestOptions::AUTH => [
                config('services.viva.client_id'),
                config('services.viva.client_secret'),
            ],
        ];
    }

    public function getAccessToken() : string
    {
        if (is_null($this->accessToken)) {
            $this->accessToken = (new OAuth($this))->getAccessToken()->access_token;
        }

        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken) : self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * Get the response body.
     *
     * @param  \Psr\Http\Message\ResponseInterface $response
     * @return \stdClass
     *
     * @throws \Sebdesign\VivaPayments\VivaException
     */
    protected function getBody(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), false, 512, JSON_BIGINT_AS_STRING);

        if (isset($body->ErrorCode) && $body->ErrorCode !== 0) {
            throw new VivaException($body->ErrorText, $body->ErrorCode);
        }

        return $body;
    }

    /**
     * Get the URL.
     *
     * @return \Psr\Http\Message\UriInterface
     */
    public function getUrl() : UriInterface
    {
        return $this->client->getConfig('base_uri');
    }

    /**
     * Get the Guzzle client.
     *
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
