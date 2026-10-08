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

    // relleno exigido por Socialite; las credenciales reales se fijan en SsoController
    'azure' => [
        'client_id' => 'definido-por-codigo',
        'client_secret' => 'definido-por-codigo',
        'redirect' => 'definido-por-codigo',
    ],

    'sso' => [
        'activo' => env('SSO_ACTIVO', false),
        'solo' => env('SSO_SOLO', false),
        'client_id' => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        // tenants permitidos y, por cada uno, los dominios de correo que pueden entrar
        'tenants' => [
            'panal' => [
                'tenant_id' => env('SSO_PANAL_TENANT'),
                'dominios' => ['panalsas.com'],
            ],
            'levapan' => [
                'tenant_id' => env('SSO_LEVAPAN_TENANT'),
                'dominios' => [
                    'levapan.com',
                    'levacolsas.com',
                    'levapan.com.ec',
                    'levapan.com.do',
                ],
            ],
        ],
    ],

];
