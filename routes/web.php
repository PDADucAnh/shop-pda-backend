<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('storage/{any}', function ($path) {
    // 1. Xác định đường dẫn file thật trong storage/app/public
    $realPath = storage_path('app/public/' . $path);

    // 2. Kiểm tra file có tồn tại không
    if (!file_exists($realPath)) {
        abort(404); // Không thấy thì báo lỗi 404
    }

    // 3. Trả về nội dung file với Header đúng loại ảnh
    $file = file_get_contents($realPath);
    $type = mime_content_type($realPath);

    return Response::make($file, 200)->header("Content-Type", $type);
})->where('any', '.*'); // Regex .* để chấp nhận cả dấu gạch chéo trong đường dẫn
