<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'register', 'logout'],

    'allowed_methods' => ['*'],

    // QUAN TRỌNG: Thay '*' bằng địa chỉ cụ thể của frontend
    // Thêm cả localhost và 127.0.0.1 để chắc chắn
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // QUAN TRỌNG: Đặt thành true để tránh lỗi CORS khi có Auth
    'supports_credentials' => false,
];