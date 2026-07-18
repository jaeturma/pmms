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

];
