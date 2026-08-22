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
        'default_reset_password' => env('PMMS_DEFAULT_RESET_PASSWORD'),
    ],

    'results' => [
        'signed_result_form_required' => (bool) env('PMMS_SIGNED_RESULT_FORM_REQUIRED', true),
        'supporting_documents_public' => (bool) env('PMMS_RESULT_DOCUMENTS_PUBLIC', false),
    ],

];
