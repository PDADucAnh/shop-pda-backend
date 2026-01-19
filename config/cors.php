<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // SỬA DÒNG NÀY: Thêm 'auth/*' hoặc để '*' để chấp nhận mọi đường dẫn
    'paths' => ['api/*', 'auth/*', 'sanctum/csrf-cookie', 'login', 'register', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // Chấp nhận tất cả (Vercel, Localhost...)

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // Bắt buộc phải là false nếu allowed_origins là '*'
];