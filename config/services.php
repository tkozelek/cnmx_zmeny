<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party Services such
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

    /*
    |--------------------------------------------------------------------------
    | Rozpis suggestions
    |--------------------------------------------------------------------------
    | Gemini, configured in config/gemini.php by google-gemini-php/laravel.
    | Optional: with no GEMINI_API_KEY set, AiRozpisSuggestionService reports
    | itself disabled and the "AI navrhni rozpis" button never renders — the
    | rozpis builder works fully without it.
    */

    /*
    |--------------------------------------------------------------------------
    | Test accounts (app:import-legacy-data)
    |--------------------------------------------------------------------------
    | Kept out of the codebase since this repo is public - see
    | App\Console\Commands\ImportLegacyData::ensureTestUsers().
    */
    'test_users' => [
        'brigadnik_email' => env('TEST_USER_BRIGADNIK_EMAIL', 'brigadnik@test.sk'),
        'admin_email' => env('TEST_USER_ADMIN_EMAIL', 'admin@test.sk'),
        'password' => env('TEST_USER_PASSWORD'),
    ],

];
