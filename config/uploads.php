<?php

return [

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    |
    | Central configuration for the PMMS file upload foundation. Files are
    | stored on the configured disk under the configured directory. Allowed
    | types and the maximum size apply to every generic upload.
    |
    */

    'disk' => env('UPLOADS_DISK', 'local'),

    'directory' => 'uploads',

    'max_kb' => (int) env('UPLOADS_MAX_KB', 10240),

    'allowed_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
    ],

];
