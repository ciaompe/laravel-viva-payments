# Viva Payments for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sebdesign/laravel-viva-payments.svg?style=flat-square)](https://packagist.org/packages/sebdesign/laravel-viva-payments)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://travis-ci.org/sebdesign/laravel-viva-payments.svg)](https://travis-ci.org/sebdesign/laravel-viva-payments)
[![Quality Score](https://img.shields.io/scrutinizer/g/sebdesign/laravel-viva-payments.svg?style=flat-square)](https://scrutinizer-ci.com/g/sebdesign/laravel-viva-payments)

[![VivaPayments logo](https://camo.githubusercontent.com/7f0b41d204f5c27c416a83fa0bc8d1d1e45cf495/68747470733a2f2f7777772e766976617061796d656e74732e636f6d2f436f6e74656e742f696d672f6c6f676f2e737667 "VivaPayments logo")](https://www.vivapayments.com/)

This package provides an interface for the Viva Wallet API. It handles the **Redirect Checkout**, **Native Checkout**, and **Mobile Checkout** payment methods, as well as **Webhooks**.

Check out the official Viva Wallet Developer Portal for detailed instructions on the APIs and more: https://developer.vivawallet.com

Also the Viva Wallet Develop Portal has detailed instructions on the APIs: https://developer.vivawallet.com

**Note:** This project is not a certified package, and I'm not affiliated with Viva Payments in any way.

## Table of Contents

- [Setup](#setup)
    - [Installation](#installation)
    - [Service Provider](#service-provider)
    - [Configuration](#configuration)
- [Simple Checkout](#simple-checkout)
- [Redirect Checkout](#redirect-checkout)
    - [Create a payment order](#create-a-payment-order)
    - [Redirect to the Viva checkout page](#redirect-to-the-viva-checkout-page)
    - [Confirm the transaction](#confirm-the-transaction)
    - [Full example](#full-example)
- [Native Checkout v1 (deprecated)](#native-checkout-v1)
    - [Display the payment form](#display-the-payment-form)
    - [Process the payment](#process-the-payment)
- [Native Checkout v2 3DS](#native-checkout-v2)
- [Mobile Checkout](#mobile-checkout)
    - [Create a payment order](#create-a-payment-order)
    - [Card Tokenization](#card-tokenization)
    - [Check installments](#check-installments)
    - [Process the payment](#process-the-payment)
- [Handling Webhooks](#handling-webhooks)
    - [Extend the controller](#extend-the-controller)
    - [Define the route](#define-the-route)
    - [Exclude from CSRF protection](exclude-from-csrf-protection)
- [API Methods](#api-methods)
    - [Orders](#orders)
        - [Create payment order](#create-payment-order)
        - [Retrieve order](#retrieve-order)
        - [Cancel order](#cancel-order)
        - [Update order](#update-order)
    - [Transactions](#transactions)
        - [Create a new transaction](#create-a-new-transaction)
        - [Create recurring transaction](#create-recurring-transaction)
        - [Retrieve transactions](#retrieve-transactions)
        - [Cancel transaction](#cancel-transaction)
    - [Cards](#cards)
        - [Get card token using card details](#get-card-token-using-card-details)
        - [Get charge token using card details](#get-charge-token-using-card-details)
        - [Get card token using the charge token](#get-card-token-using-the-charge-token)
        - [Get charge token using card token](#get-charge-token-using-card-token)
        - [Installments check](#check-installments)
    - [Payment Sources](#payment-sources)
        - [Add source](#add-source)
    - [Webhooks](#webhooks)
        - [Get an authorization code](#get-an-authorization-code)
- [Exceptions](#exceptions)
- [Tests](#tests)

## Setup

#### Installation

Install the package through Composer.

This package requires Laravel 5.0 or higher, and uses Guzzle to make API calls. Use the appropriate version according to your dependencies.

| Viva Payments for Laravel   | Guzzle  | Laravel |
|-----------------------------|---------|---------|
| ~1.0                        | ~5.0    | ~5.0    |
| ~2.0                        | ~6.0    | ~5.0    |
| ~3.0                        | ~6.0    | ~5.5    |
| ~4.0                        | ~6.0    | ~6.0    |

```
composer require sebdesign/laravel-viva-payments
```

#### Service Provider

This package supports auto-discovery for Laravel 5.5.

If you are using an older version, add the following service provider in your `config/app.php`.

```php
'providers' => [
    Sebdesign\VivaPayments\VivaPaymentsServiceProvider::class,
],
```

#### Configuration

Add the following array in your `config/services.php`.

```php
'viva' => [
    'api_key' => env('VIVA_API_KEY'),
    'merchant_id' => env('VIVA_MERCHANT_ID'),
    'public_key' => env('VIVA_PUBLIC_KEY'),
    'environment' => env('VIVA_ENVIRONMENT', 'production'),
],
```

The `api_key` and `merchant_id` can be found in the *Settings > API Access* section of your profile.

> Read more about API authentication on the Developer Portal: https://developer.vivawallet.com/authentication-methods

The `public_key` is only needed for the *Native Checkout* and the *Mobile Checkout*.

The `environment` can be either `production` or `demo`.

> To simulate a successful payment on the demo environment, use the card number 4111 1111 1111 1111 with any valid date and 111 for the CVV2.

## Simple Checkout

A simple, cross-device payment form, in a user-friendly interface working within your website. Our easiest solution to implement.

> Read more about the simple checkout process on the Developer Portal: https://developer.vivawallet.com/online-checkouts/simple-checkout

### Step 1: Display the button

Follow the instructions: https://developer.vivawallet.com/online-checkouts/simple-checkout/#step-1-display-the-button

### Step 2: Make the charge

Having the charge token submitted to your server, enables you to proceed with applying the actual transaction to your customer’s account/credit card. This can be achieved in your controller as shown below:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sebdesign\VivaPayments\Transaction;
use Sebdesign\VivaPayments\VivaException;

class CheckoutController extends Controller

    /**
     * Create a transaction and redirect to the checkout page.
     *
     * @param  \Illuminate\Http\Request            $request
     * @param  \Sebdesign\VivaPayments\Transaction $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Transaction $transaction)
    {
        try {
            $response = $transaction->create([
                'PaymentToken' => $request->vivaWalletToken,
            ]);
        } catch (VivaException $e) {
            return back()->withErrors($e->getMessage());
        }

        if ($response->StatusId === Transaction::COMPLETED) {
            $status = 'The transaction has been completed successfully.';
        } else {
            /**
             * Please see transaction statuses documentation for more information:
             *
             * @link https://developer.vivawallet.com/api-reference-guide/payment-api/transaction-statuses-and-types
             */
            $status = 'The transaction was not completed.';
        }

        return view('order/success', compact('status'));
    }
```

## Redirect Checkout

Redirect checkout is a simple three-step process, where you create the payment order, redirect the customer to the Viva Wallet Payment gateway then confirm the transaction.

> Read more about the redirect checkout process on the Developer Portal: https://developer.vivawallet.com/online-checkouts/redirect-checkout

The following guide will walk you through the necessary steps:

#### Create the payment order

The first argument is the amount requested in cents. All the parameters in the second argument are optional. Check out all the supported [Optional Parameters](https://developer.vivawallet.com/api-reference-guide/payment-api/#OP).

```php
$order = app(Sebdesign\VivaPayments\Order::class);

$orderCode = $order->create(100, [
    'FullName'      => 'Customer Name',
    'Email'         => 'customer@domain.com',
    'SourceCode'    => 'Default',
    'MerchantTrns'  => 'Order reference',
    'CustomerTrns'  => 'Description that the customer sees',
]);
```

#### Redirect to the Viva checkout page

```php
$checkoutUrl = $order->getCheckoutUrl($orderCode);

return redirect()->away($checkoutUrl);
```

#### Confirm the transaction

```php
$order = app(Sebdesign\VivaPayments\Order::class);

$response = $order->get(request('orderCode'));
```

### Full example

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sebdesign\VivaPayments\Order;
use Sebdesign\VivaPayments\VivaException;

class CheckoutController extends Controller
{
    /**
     * Create a payment order and redirect to the checkout page.
     *
     * @param  \Illuminate\Http\Request          $request
     * @param  \Sebdesign\VivaPayments\Order     $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkout(Request $request, Order $order)
    {
        try {
            $orderCode = $order->create(100, [
                'FullName'      => 'Customer Name',
                'Email'         => 'customer@domain.com',
                'SourceCode'    => 'Default',
                'MerchantTrns'  => 'Order reference',
                'CustomerTrns'  => 'Description that the customer sees',
            ]);
        } catch (VivaException $e) {
            return back()->withErrors($e->getMessage());
        }

        $checkoutUrl = $order->getCheckoutUrl($orderCode);

        return redirect()->away($checkoutUrl);
    }

    /**
     * Redirect from the checkout page and get the order details from the API.
     *
     * @param  \Illuminate\Http\Request          $request
     * @param  \Sebdesign\VivaPayments\Order     $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirm(Request $request, Order $order)
    {
        try {
            $response = $order->get($request->get('orderCode'));
        } catch (VivaException $e) {
            return back()->withErrors($e->getMessage());
        }

        switch ($response->StateId) {
            case Order::PENDING:
                $state = 'The order is pending.';
                break;
            case Order::EXPIRED:
                $state = 'The order has expired.';
                break;
            case Order::CANCELED:
                $state = 'The order has been canceled.';
                break;
            case Order::PAID:
                $state = 'The order is paid.';
                break;
        }

        return view('order/success', compact('state'));
    }
}
```

## Native Checkout v1

Follow the **steps 1 through 7** described in the Developer Portal: https://developer.vivawallet.com/online-checkouts/native-checkout-v1

Below is an example of the last step using this package.

### Display the payment form

> See: https://developer.vivawallet.com/online-checkouts/native-checkout-v1/#step-1-build-a-custom-payment-form

```php
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title></title>
</head>
<body>
    <form action="/order/checkout" id="paymentForm" method="POST" accept-charset="UTF-8">
        @csrf

        <label for="txtCardHolder">Cardholder name:</label>
        <input id="txtCardHolder" data-vp="cardholder" type="text">

        <label for="txtCardNumber">Card number:</label>
        <input id="txtCardNumber" data-vp="cardnumber" type="number">

        <div id="divInstallments" style="display: none;">
            <label for="installments">Installments:</label>
            <select id="installments" name="installments"></select>
        </div>

        <label for="txtCCV">CVV:</form>
        <input id="txtCCV" data-vp="cvv" type="number">

        <label for="txtExpMonth">Expiration Month:</label>
        <input id="txtExpMonth" data-vp="month" type="number">

        <label for="txtExpYear">Expiration Year:</label>
        <input id="txtExpYear" data-vp="year" type="number">

        <input name="token" id="token" type="hidden">

        <button type="button">Submit</button>
    </form>

    <script src="https://code.jquery.com/jquery-latest.min.js"></script>
    <script src="https://demo.vivapayments.com/web/checkout/js"></script>

    <script>
        $(document).ready(function () {
            var $paymentForm = $('#paymentForm');
            var $installments = $paymentForm.find('#divInstallments');

            VivaPayments.cards.setup({
                publicKey: '{!! config("services.viva.public_key") !!}',
                baseURL: '{!! app(Sebdesign\VivaPayments\Client::class)->getUrl() !!}',
                cardTokenHandler: function (response) {
                    if (!response.Error) {
                        $paymentForm.find('#token').val(response.Token);
                        $paymentForm.submit();
                    } else {
                        alert(response.Error);
                    }
                },
                installmentsHandler: function (response) {
                    var $select = $installments.find('select');

                    if (!response.Error) {
                        if (response.MaxInstallments === 0) {
                            $select.empty();
                            $installments.hide();
                            return;
                        }

                        for (i = 1; i <= response.MaxInstallments; i++) {
                            $select.append($("<option>").val(i).text(i));
                        }

                        $installments.show();
                    } else {
                        alert(response.Error);
                    }
                }
            });

            $paymentForm.find('button').click(function (event) {
                event.preventDefault();
                VivaPayments.cards.requestToken();
            });
        });
    </script>
</body>
</html>
```

### Process the payment

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sebdesign\VivaPayments\Order;
use Sebdesign\VivaPayments\Transaction;
use Sebdesign\VivaPayments\VivaException;

class CheckoutController extends Controller
{
    /**
     * Create a payment order and a new transaction with the token from the form.
     *
     * @param  \Illuminate\Http\Request             $request
     * @param  \Sebdesign\VivaPayments\Order        $order
     * @param  \Sebdesign\VivaPayments\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkout(Request $request, Order $order, Transaction $transaction)
    {
        try {
            $orderCode = $order->create(100, [
                'FullName'      => 'Customer Name',
                'Email'         => 'customer@domain.com',
                'SourceCode'    => 'Default',
                'MerchantTrns'  => 'Order reference',
                'CustomerTrns'  => 'Description that the customer sees',
            ]);

            $response = $transaction->create([
                'OrderCode'     => $orderCode,
                'SourceCode'    => 'Default',
                'CreditCard'    => [
                    'Token'     => $request->get('token'),
                ]
            ]);
        } catch (VivaException $e) {
            return back()->withErrors($e->getMessage());
        }

        if ($response->StatusId !== Transaction::COMPLETED) {
            return redirect('order/failure');
        }

        return redirect('order/success');
    }
}
```

## Native Checkout v2

> See: https://developer.vivawallet.com/online-checkouts/native-checkout-v2/

### Setup instructions

> See: https://developer.vivawallet.com/online-checkouts/native-checkout-v2/#setup-instructions

```php
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title></title>
    </head>

    <body>
        <form action="/order/checkout" id="payment-form" method="POST" accept-charset="UTF-8">
            @csrf
            <div class="form-row">
                <label>
                    <span>Cardholder Name</span>
                    <input type="text" data-vp="cardholder" size="20" name="txtCardHolder" autocomplete="off"/>
                </label>
            </div>

            <div class="form-row">
                <label>
                    <span>Card Number</span>
                    <input type="text" data-vp="cardnumber" size="20" name="txtCardNumber" autocomplete="off"/>
                </label>
            </div>

            <div class="form-row">
                <label>
                    <span>CVV</span>
                    <input type="text" data-vp="cvv" name="txtCVV" size="4" autocomplete="off"/>
                </label>
            </div>

            <div class="form-row">
                <label>
                    <span>Expiration (MM/YYYY)</span>
                    <input type="text" data-vp="month" size="2" name="txtMonth" autocomplete="off"/>
                </label>
                <span> / </span>
                <input type="text" data-vp="year" size="4" name="txtYear" autocomplete="off"/>
            </div>

            <div class="form-row">
                <label>
                    <span>Installments</span>
                    <select id="js-installments" name="drpInstallments" style="display:none"></select>
                </label>
            </div>

            <input name="token" id="token" type="hidden">

            <input type="button" id="submit" value="Submit Payment" />
        </form>

        <script type="text/javascript" src="https://www.vivapayments.com/web/checkout/v2/js"></script>
        <script>
            var $paymentForm = $('#payment-form');
            var $installments = $paymentForm.find('#js-installments');

            VivaPayments.cards.setup({
                authToken: {{ app(Sebdesign\Viva\Client::getAccessToken()) }},
                baseURL: 'https://demo-api.vivapayments.com',
                cardHolderAuthOptions: {
                    cardHolderAuthPlaceholderId: 'threed-secure-pane',
                    cardHolderAuthInitiated: function() {
                        alert('Cardholder authentication started!');
                    },
                    cardHolderAuthFinished: function() {
                        alert('Cardholder authentication has finished');
                    },
                    installmentsHandler: function (maxInstallments) {
                        if (MaxInstallments === 0) {
                            $installments.empty();
                            $installments.hide();
                            return;
                        }

                        for (i = 1; i <= response.MaxInstallments; i++) {
                            $installments.append($("<option>").val(i).text(i));
                        }

                        $installments.show();
                    }
                }
            });

            $paymentForm.find('button').click(function (event) {
                event.preventDefault();

                VivaPayments.cards.requestToken({
                    amount: 1321, // amount is in currency's minor unit of measurement
                    installments: $installments.val(),
                    authenticateCardholder: true
                }).done(function (responseData) {
                    console.log(`Here is the charge token: ${responseData.chargeToken}`);

                    $paymentForm.find('#token').val(responseData.chargeToken);
                    $paymentForm.submit();
                });
            });

        </script>
    </body>
</html>
```

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sebdesign\VivaPayments\Order;
use Sebdesign\VivaPayments\Transaction;
use Sebdesign\VivaPayments\VivaException;

class CheckoutController extends Controller
{
    /**
     * Create a payment order and a new transaction with the token from the form.
     *
     * @param  \Illuminate\Http\Request             $request
     * @param  \Sebdesign\VivaPayments\Order        $order
     * @param  \Sebdesign\VivaPayments\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkout(Request $request, Order $order, Transaction $transaction)
    {
        try {
            $orderCode = $order->create(100, [
                'FullName'      => 'Customer Name',
                'Email'         => 'customer@domain.com',
                'SourceCode'    => 'Default',
                'MerchantTrns'  => 'Order reference',
                'CustomerTrns'  => 'Description that the customer sees',
            ]);

            $response = $transaction->create([
                'OrderCode'     => $orderCode,
                'SourceCode'    => 'Default',
                'CreditCard'    => [
                    'Token'     => $request->get('token'),
                ]
            ]);
        } catch (VivaException $e) {
            return back()->withErrors($e->getMessage());
        }

        if ($response->StatusId !== Transaction::COMPLETED) {
            return redirect('order/failure');
        }

        return redirect('order/success');
    }
}
```

## Mobile Checkout

### Create a payment order

```php
$order = app(Sebdesign\VivaPayments\Order::class);

$orderCode = $order->create(100, [
    'FullName'      => 'Customer Name',
    'Email'         => 'customer@domain.com',
    'SourceCode'    => 'Default',
    'MerchantTrns'  => 'Order reference',
    'CustomerTrns'  => 'Description that the customer sees',
]);
```

### Card Tokenization

```php
$card = app(Sebdesign\VivaPayments\Card::class);

$cardToken = $card->cardToken('Customer Name', '4111 1111 1111 1111', 111, 03, 2016);
```

### Check installments

```php
$card = app(Sebdesign\VivaPayments\Card::class);

$maxInstallments = $card->installments('4111 1111 1111 1111');
```

### Process the payment

```php
$transaction = app(Sebdesign\VivaPayments\Transaction::class);

$response = $transaction->create([
    'OrderCode'     => $orderCode,
    'SourceCode'    => 'Default',
    'Installments'  => $maxInstallments,
    'CreditCard'    => [
        'Token'     => $cardToken,
    ]
]);
```

## Handling Webhooks

Viva Payments supports Webhooks, and this package offers a controller which can be extended to handle incoming notification events.

> Read more about the Webhooks on the Developer Portal: https://developer.vivawallet.com/api-reference-guide/payment-api/webhooks

### Extend the controller

You can make one controller to handle all the events, or make a controller for each event. Either way, your controllers must extend the `Sebdesign\VivaPayments\WebhookController`. The webhook verification is handled automatically.

For the moment, Viva Payment offers the *Create Transaction* and *Cancel/Refund Transaction* events. To handle those events, you controller must extend the `handleCreateTransaction` and a `handleRefundTransaction` methods respectively. For any other event that might be available, extend the `handleEventNotification` method.

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sebdesign\VivaPayments\WebhookController as BaseController;

class WebhookController extends BaseController
{
    /**
     * Handle a Create Transaction event notification.
     *
     * @param  \Illuminate\Http\Request $request
     */
    protected function handleCreateTransaction(Request $request)
    {
        $event = $request->EventData;
    }

    /**
     * Handle a Refund Transaction event notification.
     *
     * @param  \Illuminate\Http\Request $request
     */
    protected function handleRefundTransaction(Request $request)
    {
        $event = $request->EventData;
    }

    /**
     * Handle any other type of event notification.
     *
     * @param  \Illuminate\Http\Request $request
     */
    protected function handleEventNotification(Request $request)
    {
        $event = $request->EventData;
    }
}
```

### Define the route

In your `routes/web.php` define the following route for each webhook you have in your profile, replacing the URI(s) and your controller(s) accordingly.

```php
Route::match(['post', 'get'], 'viva/webhooks', 'WebhookController@handle');
```

### Exclude from CSRF protection

Don't forget to add your webhook URI(s) to the `$except` array on your `VerifyCsrfToken` middleware.

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'viva/webhooks',
    ];
}
```

## API Methods

### Orders

##### Create payment order

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#create-payment-order

```php
$order = app(Sebdesign\VivaPayments\Order::class);

$orderCode = $order->create(100, [...]);
```

##### Retrieve order

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#retrieve-order

```php
$response = $order->get(175936509216);
```

##### Cancel order

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#cancel-payment-order

```php
$response = $order->cancel(175936509216);
```

##### Update order

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#update-order

```php
$order->update(175936509216, ['Amount' => 50]);
```

### Transactions

##### Create a new transaction

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#step-5-execute-the-payment

```php
$transaction = app(Sebdesign\VivaPayments\Transaction::class);

$response = $transaction->create([
    'OrderCode'     => 175936509216,
    'SourceCode'    => 'Default',
    'CreditCard'    => [
        'Token'     => $chargeToken,
    ]
]);
```

##### Create recurring transaction

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#create-recurring-transaction

```php
$transaction = app(Sebdesign\VivaPayments\Transaction::class);

$response = $transaction->createRecurring('252b950e-27f2-4300-ada1-4dedd7c17904', [
    'Amount'       => 500,
    'Installments' => 0,
]);
```

##### Retrieve transactions

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#retrieve-transactions

```php
// By transaction ID
$transactions = $transaction->get('252b950e-27f2-4300-ada1-4dedd7c17904');

// By order code
$transactions = $transaction->getByOrder(175936509216);

// By order date

// The date can be a string in Y-m-d format,
// or a DateTimeInterface instance like DateTime or Carbon.

$transactions = $transaction->getByDate('2016-03-11');

// By clearance date

// The date can be a string in Y-m-d format,
// or a DateTimeInterface instance like DateTime or Carbon.

$transactions = $transaction->getByClearanceDate('2016-03-11');
```

##### Cancel transaction

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#cancel-transaction

```php
$response = $transaction->cancel('252b950e-27f2-4300-ada1-4dedd7c17904', 100, 'username');
```

### Cards

#### Get card token using card details

```php
$card = app(Sebdesign\VivaPayments\Card::class);

$cardToken = $card->cardToken('Customer Name', '4111 1111 1111 1111', 111, 03, 2016);
```

#### Get charge token using card details

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#step-1-get-charge-token-using-card-details

```php
$card = app(Sebdesign\VivaPayments\Card::class);

$chargeToken = $card->chargeToken('Customer Name', '4111 1111 1111 1111', 111, 03, 2016);
```

#### Get card token using the charge token

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#step-2-get-card-token-using-the-charge-token

```php
$card = app(Sebdesign\VivaPayments\Card::class);

$cardToken = $card->getCardToken($chargeToken);
```

#### Get charge token using card token

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#step-3-get-charge-token-using-card-token

```php
$card = app(Sebdesign\VivaPayments\Card::class);

$chargeToken = $card->getChargeToken($cardToken);
```

#### Installments check

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#installments-check

```php
$maxInstallments = $card->installments('4111 1111 1111 1111');
```

### Payment Sources

##### Add source

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#add-source

```php
$source = app(Sebdesign\VivaPayments\Source::class);

$source->create('Site 1', 'site1', 'https://www.domain.com', 'order/failure', 'order/success');
```

### Webhooks

##### Get an authorization code

> See: https://developer.vivawallet.com/api-reference-guide/payment-api/#webhook-url-verification

```php
$webhook = app(Sebdesign\VivaPayments\Webhook::class);

$key = $webhook->verify();
```

## Exceptions

When the VivaPayments API returns an error, a `Sebdesign\VivaPayments\VivaException` is thrown.

For any other HTTP error a `GuzzleHttp\Exception\ClientException` is thrown.

## Tests

Unit tests are triggered by running `phpunit --group unit`.

To run functional tests you have to include a `.env` file in the root folder, containing the credentials (`VIVA_API_KEY`, `VIVA_MERCHANT_ID`, `VIVA_PUBLIC_KEY`), in order to hit the VivaPayments demo API. Then run `phpunit --group functional` to trigger the tests.
