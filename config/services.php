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

    'ihris' => [
        'base_url' => env('IHRIS_API_BASE_URL', env('IHRI_API_URL', 'https://testihris.bayambang.gov.ph/api')),
        'login_endpoint' => env('IHRIS_LOGIN_ENDPOINT', 'login'),
        'username_field' => env('IHRIS_USERNAME_FIELD', 'email'),
        'allowed_office' => env('IHRIS_ALLOWED_OFFICE', 'BDRRMC'),
        'allowed_offices' => array_filter(array_map('trim', explode('|', env(
            'IHRIS_ALLOWED_OFFICES',
            'BDRRMC|MDRRMO|Municipal Disaster Risk Reduction Management Office'
        )))),
        'admin_override_emails' => array_filter(array_map('trim', explode('|', env('IHRIS_ADMIN_OVERRIDE_EMAILS', '')))),
        'super_admin_emails' => array_filter(array_map('trim', explode('|', env(
            'IHRIS_SUPER_ADMIN_EMAILS',
            'villamorjerichoivan@gmail.com'
        )))),
        'office_uuid' => env('IHRIS_OFFICE_UUID', '6b8aa468-3d99-483a-8f3b-ba70375c102e'),
        'connect_timeout' => env('IHRIS_CONNECT_TIMEOUT', 5),
        'timeout' => env('IHRIS_TIMEOUT', 10),
        'test_login' => [
            'enabled' => env('IHRIS_TEST_LOGIN_ENABLED', false),
            'email' => env('IHRIS_TEST_LOGIN_EMAIL', 'test@bdrrmc.local'),
            'password' => env('IHRIS_TEST_LOGIN_PASSWORD', 'password'),
            'name' => env('IHRIS_TEST_LOGIN_NAME', 'BDRRMC Test Admin'),
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
