<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'billplz' => [
        'api_key' => env('BILLPLZ_API_KEY') ?: 'sandbox-api-key-ecohydrotech',
        'collection_id' => env('BILLPLZ_COLLECTION_ID') ?: 'inbmmepb',
        'x_signature_key' => env('BILLPLZ_X_SIGNATURE_KEY') ?: 'sandbox-x-signature-key-ecohydrotech',
        'sandbox' => (bool) env('BILLPLZ_SANDBOX', true),
        'endpoint' => env('BILLPLZ_SANDBOX', true)
            ? 'https://www.billplz-sandbox.com/api/v3'
            : 'https://www.billplz.com/api/v3',
    ],

];
