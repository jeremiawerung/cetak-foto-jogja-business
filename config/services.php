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

    'toko' => [
        'nama' => env('TOKO_NAMA', 'Cetak Foto Jogja'),
        'alamat' => env('TOKO_ALAMAT', 'Jl. Tempel, Gendol, Margorejo, Kec. Tempel, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55552'),
    ],

    'bank' => [
        'nama_bank' => env('BANK_NAMA', 'BCA'),
        'no_rekening' => env('BANK_NO_REKENING', '1234567890'),
        'atas_nama' => env('BANK_ATAS_NAMA', 'Cetak Foto Jogja'),
    ],

    'qris' => [
        'gambar' => env('QRIS_GAMBAR', 'images/pembayaran/qris.png'),
    ],

    // RajaOngkir v2 (by Komerce) - https://dev-collaborator.komerce.id
    'rajaongkir' => [
        'base_url' => env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),
        'api_key' => env('RAJAONGKIR_API_KEY'),
        'origin_id' => env('RAJAONGKIR_ORIGIN_ID'),
        'origin_label' => env('RAJAONGKIR_ORIGIN_LABEL'),
        'courier' => env('RAJAONGKIR_COURIER', 'jne:jnt:sicepat:anteraja:ninja'),
    ],

    'kartu_pelajar' => [
        'pdftotext_path' => env('PDFTOTEXT_PATH', 'pdftotext'),
        'duplicate_threshold_seconds' => env('KARTU_PELAJAR_DUPLICATE_THRESHOLD', 5),
        'fuzzy_name_threshold' => env('KARTU_PELAJAR_FUZZY_NAME_THRESHOLD', 0.75),
        'fuzzy_auto_match_threshold' => env('KARTU_PELAJAR_FUZZY_AUTO_MATCH_THRESHOLD', 0.95),
        'nisn_crosscheck_name_threshold' => env('KARTU_PELAJAR_NISN_CROSSCHECK_THRESHOLD', 0.5),
        'ai_verification' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'model' => env('KARTU_PELAJAR_AI_MODEL', 'deepseek-flash'),
            'batch_size' => env('KARTU_PELAJAR_AI_BATCH_SIZE', 25),
        ],
    ],

    'verifikasi_siswa' => [
        'google_service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH', storage_path('app/google/service-account.json')),
        'sync_interval_minutes' => env('VERIFIKASI_SISWA_SYNC_INTERVAL', 15),
        'fuzzy_name_threshold' => env('VERIFIKASI_SISWA_FUZZY_THRESHOLD', 0.75),
        'nisn_crosscheck_name_threshold' => env('VERIFIKASI_SISWA_NISN_CROSSCHECK_THRESHOLD', 0.5),
    ],

];
