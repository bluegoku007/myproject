<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration
    |--------------------------------------------------------------------------
    |
    | This file is where you may configure your settings for handling Cross-Origin
    | Resource Sharing (CORS). By default, all cross-origin requests are denied.
    | You can enable CORS for specific routes or allow all origins.
    |
    */

    'paths' => ['api/*', 'login', 'register','sanctum/csrf-cookie'], // API and routes like login and register that you want to allow CORS for

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | The allowed HTTP methods for cross-origin requests. You may specify
    | any number of HTTP methods that should be allowed for cross-origin requests.
    | By default, we allow all HTTP methods.
    |
    */

    'allowed_methods' => ['*'], // Allow all HTTP methods (GET, POST, etc.)

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | The allowed origins for cross-origin requests. You can specify
    | individual domains or allow all origins with a wildcard.
    | For production, it's recommended to specify a list of allowed origins.
    |
    */

    'allowed_origins' => ['*'], // Replace with your frontend URL

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | The allowed headers that clients can send when making cross-origin requests.
    | You may set specific headers to allow or use the wildcard "*" to allow all headers.
    |
    */

    'allowed_headers' => ['*'], // Allow all headers

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | The headers that are exposed to the browser in a cross-origin request.
    | You can specify specific headers or leave it as false.
    |
    */

    'exposed_headers' => false, // Expose no headers by default

    /*
    |--------------------------------------------------------------------------
    | Maximum Age
    |--------------------------------------------------------------------------
    |
    | The maximum age (in seconds) that the browser should cache preflight requests.
    | Set this to 0 to disable caching.
    |
    */

    'max_age' => 0, // 0 to disable caching

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Whether or not the browser should send cookies and HTTP authentication with requests.
    | Set this to true if you need to support cookies or authentication headers.
    |
    */

    'supports_credentials' => true, // Allow credentials (cookies, auth tokens, etc.)

];
