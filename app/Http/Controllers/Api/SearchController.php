<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        try {
            $keyword = $request->input('keyword');
            
            // Xử lý từ khóa
            $keyword = trim($keyword);

            if (!$keyword) {
                return response()->json([
                    'status' => true,
                    'data' => ['products' => [], 'posts' => []]
                ]);
            }

            // --- 1. TÌM KIẾM SẢN PHẨM ---
            // Sửa tên cột khớp với Database: price -> price_buy, image -> thumbnail
            $products = Product::where('status', 1)
                ->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%");
                })
                // QUAN TRỌNG: Chỉ select những cột CÓ THẬT trong database
                ->select('id', 'name', 'slug', 'thumbnail', 'price_buy')
                ->limit(10)
                ->get();

            // Map dữ liệu để khớp với Frontend (Frontend đang dùng .image và .price)
            $products->transform(function ($item) {
                $item->image = $item->thumbnail; // Gán thumbnail vào image
                $item->price = $item->price_buy; // Gán price_buy vào price
                $item->price_sale = $item->price_buy; // Tạm thời gán sale bằng giá gốc (hoặc logic khác tùy bạn)
                return $item;
            });

            // --- 2. TÌM KIẾM BÀI VIẾT ---
            // Kiểm tra xem bảng posts có tồn tại và có cột title không trước khi query
            // (Code an toàn để tránh lỗi nếu chưa có bảng posts)
            $posts = [];
            try {
                 $posts = Post::where('status', '!=', 0) // Giả sử status khác 0 là hiện
                    ->where('title', 'like', "%{$keyword}%")
                    ->select('id', 'title', 'slug', 'image', 'type', 'created_at')
                    ->limit(5)
                    ->get();
            } catch (\Exception $e) {
                // Nếu lỗi query post (do thiếu bảng/cột) thì bỏ qua, vẫn trả về product
            }

            return response()->json([
                'status' => true,
                'message' => 'Tìm kiếm thành công',
                'data' => [
                    'products' => $products,
                    'posts' => $posts
                ]
            ]);

        } catch (\Exception $e) {
            // Trả về lỗi chi tiết để Frontend đọc được thay vì lỗi 500 trắng
            return response()->json([
                'status' => false,
                'message' => 'Lỗi Server: ' . $e->getMessage()
            ], 500);
        }
    }
}