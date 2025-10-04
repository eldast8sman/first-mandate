<?php

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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'flutterwave' => [
        'dev' => [
            'client_id' => env('FLUTTERWAVE_CLIENT_ID_DEV'),
            'client_secret' => env('FLUTTERWAVE_CLIENT_SECRET_DEV'),
            'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY_DEV'),
            'base_url' => env('FLUTTERWAVE_BASE_URL_DEV', 'https://api.flutterwave.cloud/developersandbox/'),
        ],
        'prod' => [
            'client_id' => env('FLUTTERWAVE_CLIENT_ID_PROD'),
            'client_secret' => env('FLUTTERWAVE_CLIENT_SECRET_PROD'),
            'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY_PROD'),
            'base_url' => env('FLUTTERWAVE_BASE_URL_PROD', 'https://api.flutterwave.cloud/f4bexperience/'),
        ],
        'environment' => env('FLUTTERWAVE_ENV', 'PRODUCTION'),
    ]

];
