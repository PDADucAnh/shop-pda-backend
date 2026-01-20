<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    */

    // Cấu hình qua URL (ƯU TIÊN DÙNG CÁI NÀY)
    'cloud_url' => env('CLOUDINARY_URL', 'cloudinary://592767544744234:9VUl-XPI8pYDLmO7gSz-_wwXuK4@dskkphbyf'),

    // Cấu hình riêng lẻ (backup)
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', 'dskkphbyf'),
    'api_key' => env('CLOUDINARY_API_KEY', '592767544744234'),
    'api_secret' => env('CLOUDINARY_API_SECRET', '9VUl-XPI8pYDLmO7gSz-_wwXuK4'),

    'secure' => true,

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET', 'ml_default'), // Thêm default preset
];