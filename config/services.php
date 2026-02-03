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

    // Central OAuth App - Each user connects their own Google account
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/platforms/callback/google',
    ],

    // OpenAI API für AI-generierte Review-Antworten
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    // Stripe Pricing Tables (Light + Dark Mode)
    // Erstelle zwei Pricing Tables im Stripe Dashboard: Produkte → Pricing Tables
    // Light: Hintergrund #ffffff, Button #171717
    // Dark: Hintergrund #0A0A0A, Button #FAFAFA
    'stripe' => [
        'pricing_table_id' => env('STRIPE_PRICING_TABLE_ID'),
        'pricing_table_id_dark' => env('STRIPE_PRICING_TABLE_ID_DARK'),
    ],

];
