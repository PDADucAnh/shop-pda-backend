<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    */
    'cloud_url' => env('CLOUDINARY_URL'),
    
    // Các biến lẻ để fix lỗi Undefined array key "cloud"
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key'    => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),

    'secure' => true,

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
];