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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),

        'model' => env(
            'GEMINI_MODEL',
            'gemini-2.5-flash'
        ),

        'endpoint' => env(
            'GEMINI_ENDPOINT',
            'https://generativelanguage.googleapis.com/v1beta/interactions'
        ),
    ],

    'pathwise' => [
        // Mailbox(es) of the EDP office for "new / resubmitted application"
        // emails, comma separated. When set, only these receive the email;
        // Super Admin accounts still get the in-app notification. See
        // App\Support\EdpNotificationRecipients.
        'edp_notification_email' => env('PATHWISE_EDP_NOTIFICATION_EMAIL'),
    ],

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),

        // Checkout payment methods, comma-separated: gcash, paymaya (Maya), card.
        // See App\Support\PaymentMethods.
        'payment_methods' => env('PAYMONGO_PAYMENT_METHODS', 'gcash,card,paymaya'),

        // PayMongo webhook endpoint signing secret (whsk_...), not the API key.
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),

        // Reject stale signed requests to reduce replay risk.
        'webhook_tolerance' => (int) env(
            'PAYMONGO_WEBHOOK_TOLERANCE',
            300
        ),
    ],

];
