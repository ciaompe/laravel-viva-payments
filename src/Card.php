<?php

namespace Sebdesign\VivaPayments;

use Illuminate\Support\Carbon;

class Card
{
    const ENDPOINT = '/api/cards/';

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
     * Get a token for the credit card.
     *
     * @param  string $name   The cardholder's name
     * @param  string $number The credit card number
     * @param  int    $cvc    The CVC number
     * @param  int    $month  The expiration month
     * @param  int    $year   The expiration year
     * @return string
     */
    public function cardToken(
        string $name,
        string $number,
        int $cvc,
        int $month,
        int $year
    ) : string {
        $this->client->useBasicAuthentication();

        $token = $this->client->post(self::ENDPOINT, [
            \GuzzleHttp\RequestOptions::FORM_PARAMS => [
                'CardHolderName'    => $name,
                'Number'            => $this->normalizeNumber($number),
                'CVC'               => $cvc,
                'ExpirationDate'    => $this->getExpirationDate($month, $year),
            ],
            \GuzzleHttp\RequestOptions::QUERY => [
                'key'               => config('services.viva.public_key'),
            ],
        ]);

        return $token->Token;
    }

    /**
     * Get a token for the credit card.
     *
     * @deprecated Use Card::cardToken() instead.
     *
     * @param  string $name   The cardholder's name
     * @param  string $number The credit card number
     * @param  int    $cvc    The CVC number
     * @param  int    $month  The expiration month
     * @param  int    $year   The expiration year
     * @return string
     */
    public function token(
        string $name,
        string $number,
        int $cvc,
        int $month,
        int $year
    ) : string {
        return $this->cardToken($name, $number, $cvc, $month, $year);
    }

    /**
     * Get a charge token using card details.
     *
     * @see https://developer.vivawallet.com/api-reference-guide/payment-api/#step-1-get-charge-token-using-card-details
     *
     * @param  string      $name   The cardholder's name
     * @param  string      $number The credit card number
     * @param  int         $cvc    The CVC number
     * @param  int         $month  The expiration month
     * @param  int         $year   The expiration year
     * @param  string|null $redirect  The session redirect url
     * @return string
     */
    public function chargeToken(
        string $name,
        string $number,
        int $cvc,
        int $month,
        int $year,
        ?string $redirect = null
    ) : string {
        $this->client->useOAuth2Authentication();

        $token = $this->client->post($this->getAcquiringUrl().'chargetokens', [
            \GuzzleHttp\RequestOptions::JSON => [
                'holderName'         => $name,
                'number'             => $this->normalizeNumber($number),
                'cvc'                => $cvc,
                'expirationMonth'    => $month,
                'expirationYear'     => $year,
                'SessionRedirectUrl' => $redirect,
            ],
        ]);

        return $token->chargeToken;
    }

    /**
     * Get a charge token using card details.
     *
     * @see https://developer.vivawallet.com/api-reference-guide/payment-api/#step-1-get-charge-token-using-card-details
     *
     * @param  string $name      The cardholder's name
     * @param  string $number    The credit card number
     * @param  int    $cvc       The CVC number
     * @param  int    $month     The expiration month
     * @param  int    $year      The expiration year
     * @param  int    $amount    The amount
     * @param  string $redirect  The session redirect url
     * @return string
     */
    public function nativeChargeToken(
        string $name,
        string $number,
        int $cvc,
        int $month,
        int $year,
        int $amount,
        int $installments,
        bool $authenticateCardholder,
        string $redirect = ''
    ) : string {
        $this->client->useOAuth2Authentication();

        $suffix = $authenticateCardholder ? '': ':skipauth';

        $token = $this->client->post($this->getNativeUrl().'chargetokens'.$suffix, [
            \GuzzleHttp\RequestOptions::JSON => [
                'Number'                 => $this->normalizeNumber($number),
                'CVC'                    => $cvc,
                'HolderName'             => $name,
                'ExpirationYear'         => $year,
                'ExpirationMonth'        => $month,
                'Installments'           => $installments,
                'Amount'                 => $amount,
                'AuthenticateCardholder' => $authenticateCardholder,
                'SessionRedirectUrl'     => $redirect,
            ],
        ]);
dump($token);
        return $token->chargeToken;
    }

    /**
     * Get card token using charge token.
     *
     * @see https://developer.vivawallet.com/api-reference-guide/payment-api/#step-2-get-card-token-using-the-charge-token
     *
     * @param  string $chargeToken
     * @return string
     */
    public function getCardToken(string $chargeToken) : string
    {
        $this->client->useOAuth2Authentication();

        $response = $this->client->get($this->getAcquiringUrl().'tokens', [
            \GuzzleHttp\RequestOptions::QUERY => [
                'chargeToken' => $chargeToken,
            ],
        ]);

        return $response->token;
    }

    /**
     * Get charge token using card token.
     *
     * @see https://developer.vivawallet.com/api-reference-guide/payment-api/#step-3-get-charge-token-using-card-token
     *
     * @param  string $cardToken
     * @return string
     */
    public function getChargeToken(string $cardToken) : string
    {
        $this->client->useOAuth2Authentication();

        $response = $this->client->get($this->getAcquiringUrl().'chargetokens', [
            \GuzzleHttp\RequestOptions::QUERY => [
                'token' => $cardToken,
            ],
        ]);

        return $response->chargeToken;
    }

    protected function getAcquiringUrl() : string
    {
        $environment = config('services.viva.environment');

        if ($environment === 'production') {
            return 'https://api.vivapayments.com/acquiring/v1/cards/';
        }

        if ($environment === 'demo') {
            return 'https://demo-api.vivapayments.com/acquiring/v1/cards/';
        }

        throw new \InvalidArgumentException('The Viva Payments environment must be demo or production.');
    }

    protected function getNativeUrl() : string
    {
        $environment = config('services.viva.environment');

        if ($environment === 'production') {
            return 'https://api.vivapayments.com/nativecheckout/v2/';
        }

        if ($environment === 'demo') {
            return 'https://demo-api.vivapayments.com/nativecheckout/v2/';
        }

        throw new \InvalidArgumentException('The Viva Payments environment must be demo or production.');
    }

    /**
     * Strip non-numeric characters.
     *
     * @param  string $number  The credit card number
     * @return string
     */
    protected function normalizeNumber(string $number) : string
    {
        return preg_replace('/\D/', '', $number);
    }

    /**
     * Get the expiration date.
     *
     * @param  int $month
     * @param  int $year
     * @return string
     */
    protected function getExpirationDate(int $month, int $year) : string
    {
        return Carbon::createFromDate($year, $month, 15)->toDateString();
    }

    /**
     * Check for installments support.
     *
     * @param  string $number  The credit card number
     * @return int
     */
    public function installments(string $number)
    {
        $this->client->useBasicAuthentication();

        $response = $this->client->get(self::ENDPOINT.'installments', [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'CardNumber' => $this->normalizeNumber($number),
            ],
        ]);

        return $response->MaxInstallments;
    }
}
