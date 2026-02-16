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

    'cloudflare_ai' => [
        'account_id' => env('CLOUDFLARE_AI_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_AI_API_TOKEN'),
        'playbook_token' => env('CLOUDFLARE_AI_PLAYBOOK_TOKEN'),
        'chat_model' => env('CLOUDFLARE_AI_CHAT_MODEL', '@cf/meta/llama-3.1-8b-instruct'),
        'playbook_model' => env('CLOUDFLARE_AI_PLAYBOOK_MODEL', '@cf/meta/llama-3.1-8b-instruct'),
    ],

    'gemini_ai' => [
        'api_key' => env('GEMINI_AI_API_KEY'),
        'chat_model' => env('GEMINI_AI_CHAT_MODEL', 'gemini-2.0-flash'),
        'playbook_model' => env('GEMINI_AI_PLAYBOOK_MODEL', 'gemini-2.0-flash'),
    ],

];
