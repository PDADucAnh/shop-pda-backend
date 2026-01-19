<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // 1. Lấy danh sách (Giữ nguyên)
    public function index()
    {
        $categories = Category::where('status', '!=', 0)
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'categories' => $categories
        ]);
    }

    // 2. Lấy chi tiết (Dùng cho trang Edit)
    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy danh mục'], 404);
        }
        return response()->json(['status' => true, 'category' => $category]);
    }

    // 3. Thêm mới
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories,name',
        ], [
            'name.required' => 'Tên danh mục không được để trống',
            'name.unique' => 'Tên danh mục đã tồn tại'
        ]);

        $category = new Category();
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        $category->parent_id = $request->parent_id ?? 0;
        $category->sort_order = $request->sort_order ?? 0;
        $category->description = $request->description;
        $category->status = $request->status ?? 1;
        
        // Xử lý ảnh nếu có (tương tự Product)
        if ($request->hasFile('image')) {
            $category->image = $request->file('image')->store('categories', 'public');
        }

        $category->save();

        return response()->json(['status' => true, 'message' => 'Thêm danh mục thành công', 'category' => $category], 201);
    }

    // 4. Cập nhật
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy danh mục'], 404);
        }

        $request->validate([
            'name' => 'required|unique:categories,name,' . $id,
        ]);

        $category->name = $request->name;
        if ($request->name !== $category->name) {
            $category->slug = Str::slug($request->name);
        }
        $category->parent_id = $request->parent_id ?? $category->parent_id;
        $category->sort_order = $request->sort_order ?? $category->sort_order;
        $category->description = $request->description;
        $category->status = $request->status ?? $category->status;

        if ($request->hasFile('image')) {
            $category->image = $request->file('image')->store('categories', 'public');
        }

        $category->save();

        return response()->json(['status' => true, 'message' => 'Cập nhật thành công', 'category' => $category]);
    }

    // 5. Xóa
    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy danh mục'], 404);
        }
        
        // Kiểm tra xem có sản phẩm nào thuộc danh mục này không trước khi xóa
        if ($category->products()->count() > 0) {
            return response()->json(['status' => false, 'message' => 'Không thể xóa danh mục đang chứa sản phẩm'], 400);
        }

        $category->delete();
        return response()->json(['status' => true, 'message' => 'Xóa thành công']);
    }
}