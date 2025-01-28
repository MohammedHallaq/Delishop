<?php


return [

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Specifies which origins are allowed to access your API. Using '*' will 
    | allow all origins, but it's better to specify specific domains for security.
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Allow CORS for these paths

    'allowed_methods' => ['*'], // Allow all HTTP methods

    'allowed_origins' => ['*'], // Allow all origins (change '*' to specific domains for security)

    'allowed_origins_patterns' => [], // Allow patterns (optional)

    'allowed_headers' => ['*'], // Allow all headers

    'exposed_headers' => [], // Headers exposed to the browser (if needed)

    'max_age' => 0, // Cache preflight response (in seconds)

    'supports_credentials' => false, // Set to true if using cookies or authentication headers
];
