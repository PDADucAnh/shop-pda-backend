<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    // 1. Get List (Admin & Client)
    public function index(Request $request)
    {
        // Khởi tạo query
        $query = Banner::query();

        // Lọc theo vị trí (slideshow, ads...)
        if ($request->has('position')) {
            $query->where('position', $request->position);
        }

        // Lọc theo trạng thái (Frontend gọi status=1 để lấy cái đang hiện)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sắp xếp: Ưu tiên sort_order nhỏ nhất lên đầu (ASC), sau đó đến mới nhất
        if ($request->has('sort') && $request->sort == 'sort_order') {
            $query->orderBy('sort_order', 'asc');
        }

        // Mặc định sắp xếp mới nhất
        $query->orderBy('id', 'desc');

        $banners = $query->get();

        // Xử lý Image URL (Accessor hoặc Transform trực tiếp)
        $banners->transform(function ($banner) {
            $banner->image_url = $banner->image
                ? (str_starts_with($banner->image, 'http') ? $banner->image : asset('storage/' . $banner->image))
                : null;
            return $banner;
        });

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách banner thành công',
            'banners' => $banners // Key quan trọng để khớp với frontend
        ]);
    }

    // 2. Get Detail
    public function show($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['status' => false, 'message' => 'Banner not found'], 404);
        }

        // Tự động append full URL nếu chưa dùng Accessor trong Model
        $banner->image_url = $banner->image
            ? (str_starts_with($banner->image, 'http') ? $banner->image : asset('storage/' . $banner->image))
            : null;

        return response()->json(['status' => true, 'banner' => $banner]);
    }

    // 3. Create Banner
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
            'position' => 'required|in:slideshow,ads',
        ]);

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('banners', 'public');
            }

            $banner = new Banner();
            $banner->name = $request->name;
            $banner->image = $imagePath;
            $banner->link = $request->link;
            $banner->position = $request->position;
            $banner->sort_order = $request->sort_order ?? 0;
            $banner->description = $request->description;
            $banner->status = $request->has('status') ? $request->status : 1;
            $banner->created_by = auth('api')->id() ?? 1;
            $banner->save();

            return response()->json([
                'status' => true,
                'message' => 'Tạo banner thành công',
                'data' => $banner
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 4. Update Banner
    public function update(Request $request, $id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['status' => false, 'message' => 'Banner not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|in:slideshow,ads',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            if ($request->hasFile('image')) {
                // Delete old image
                if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                    Storage::disk('public')->delete($banner->image);
                }
                $banner->image = $request->file('image')->store('banners', 'public');
            }

            $banner->name = $request->name;
            $banner->link = $request->link;
            $banner->position = $request->position;
            $banner->sort_order = $request->sort_order ?? $banner->sort_order;
            $banner->description = $request->description;

            // Chỉ cập nhật status nếu có gửi lên
            if ($request->has('status')) {
                $banner->status = $request->status;
            }

            $banner->updated_by = auth('api')->id() ?? 1;
            $banner->save();

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật banner thành công',
                'data' => $banner
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 5. Delete Banner
    public function destroy($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['status' => false, 'message' => 'Banner not found'], 404);
        }

        try {
            if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }
            $banner->delete();
            return response()->json(['status' => true, 'message' => 'Xóa banner thành công']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}