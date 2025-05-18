<?php

use App\Services\PaypalService;
use App\Services\StripeService;
use App\Services\MercadoPagoService;
use App\Services\PayUService;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'currency_conversion' => [
        'base_uri' => env('CURRENCY_CONVERSION_BASE_URI'),
        'api_key'  => env('CURRENCY_CONVERSION_API_KEY'),
    ],

    'paypal' => [
        'base_uri'      => env('PAYPAL_BASE_URI'),
        'client_id'     => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'class'         => PaypalService::class,
        'plans'         => [
            'montly' => env('PAYPAL_MONTLY_PLAN'),
            'yearly' => env('PAYPAL_YEARLY_PLAN'),
        ],
    ],

    'payu' => [
        'base_uri'      => env('PAYU_BASE_URI'),
        'account_id'    => env('PAYU_ACCOUNT_ID'),
        'merchant_id'   => env('PAYU_MERCHANT_ID'),
        'key'           => env('PAYU_KEY'),
        'secret'        => env('PAYU_SECRET'),
        'base_currency' => 'cop',
        'class'         => PayUService::class,
    ],

    'stripe' => [
        'base_uri' => env('STRIPE_BASE_URI'),
        'key'      => env('STRIPE_KEY'),
        'secret'   => env('STRIPE_SECRET'),
        'class'    => StripeService::class,
        'plans'         => [
            'montly' => env('STRIPE_MONTLY_PLAN'),
            'yearly' => env('STRIPE_YEARLY_PLAN'),
        ],
    ],

    'mercadopago' => [
        'base_uri'      => env('MERCADOPAGO_BASE_URI'),
        'key'           => env('MERCADOPAGO_KEY'),
        'secret'        => env('MERCADOPAGO_SECRET'),
        'base_currency' => 'cop',
        'class'         => MercadoPagoService::class,
    ],

];
