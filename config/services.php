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

    'airtable' => [
        'token' => env('AIRTABLE_TOKEN'),
        'base_id' => env('AIRTABLE_BASE_ID'),
        'table_cetak_foto' => env('AIRTABLE_TABLE_CETAK_FOTO', 'Order Cetak Foto'),
        'table_sewa_fotografer' => env('AIRTABLE_TABLE_SEWA_FOTOGRAFER', 'Booking Sewa Fotografer'),
    ],

    'whatsapp' => [
        'number' => env('WHATSAPP_NUMBER', '62895708600900'),
    ],

];
