<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Administrator Account
    |--------------------------------------------------------------------------
    |
    | Used by AdminUserSeeder to create or update the first administrator
    | account. Set PMMS_ADMIN_PASSWORD in the environment before seeding;
    | outside production a missing password falls back to a local default.
    |
    */

    'admin' => [
        'name' => env('PMMS_ADMIN_NAME', 'PMMS Administrator'),
        'email' => env('PMMS_ADMIN_EMAIL', 'admin@pmms.local'),
        'password' => env('PMMS_ADMIN_PASSWORD'),
    ],

    'accounts' => [
        'default_reset_password' => env('PMMS_DEFAULT_RESET_PASSWORD', 'DdOPaa2026!'),
    ],

    'athlete_photos' => [
        'max_upload_kb' => 20480,
        'max_stored_kb' => 500,
        'format' => 'jpeg',
        'passport' => ['width' => 800, 'height' => 1000, 'aspect_ratio' => '4:5'],
        'sports' => ['width' => 800, 'height' => 1000, 'aspect_ratio' => '4:5'],
        'derivatives' => [
            'thumb' => ['width' => 200, 'height' => 250],
            'card' => ['width' => 480, 'height' => 600],
        ],
    ],

    'athlete_photos' => [
        'max_upload_kb' => 20480,
        'max_stored_kb' => 500,
        'format' => 'jpeg',
        'passport' => ['width' => 800, 'height' => 1000, 'aspect_ratio' => '4:5'],
        'sports' => ['width' => 800, 'height' => 1000, 'aspect_ratio' => '4:5'],
        'derivatives' => [
            'thumb' => ['width' => 200, 'height' => 250],
            'card' => ['width' => 480, 'height' => 600],
        ],
    ],

    'results' => [
        'signed_result_form_required' => (bool) env('PMMS_SIGNED_RESULT_FORM_REQUIRED', true),
        'supporting_documents_public' => (bool) env('PMMS_RESULT_DOCUMENTS_PUBLIC', false),
    ],

];
