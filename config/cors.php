<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure CORS settings for your application. CORS is
    | automatically applied to API routes. You may customize these settings.
    |
    */

    'paths'                    => ['api/*', 'sanctum/csrf-cookie', 'api/documentation*'],

    'allowed_methods'          => ['*'],

    'allowed_origins'          => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers'          => ['*'],

    'exposed_headers'          => [],

    'max_age'                  => 0,

    'supports_credentials'     => true,

];
