<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TopicController extends Controller
{
    // 1. Lấy danh sách Topic
    public function index()
    {
        $topics = Topic::where('status', '!=', 0)->orderBy('sort_order', 'asc')->get();
        return response()->json(['status' => true, 'topics' => $topics]);
    }

    // 2. Lấy chi tiết Topic
    public function show($id)
    {
        $topic = Topic::find($id);
        if (!$topic) return response()->json(['status' => false, 'message' => 'Not found'], 404);
        return response()->json(['status' => true, 'topic' => $topic]);
    }

    // 3. Tạo mới Topic
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:topics,name',
        ]);

        $topic = new Topic();
        $topic->name = $request->name;
        $topic->slug = Str::slug($request->name);
        $topic->sort_order = $request->sort_order ?? 0;
        $topic->description = $request->description;
        $topic->status = $request->status ?? 1;
        $topic->created_at = now();
        $topic->save();

        return response()->json(['status' => true, 'message' => 'Thêm chủ đề thành công', 'topic' => $topic]);
    }

    // 4. Cập nhật Topic
    public function update(Request $request, $id)
    {
        $topic = Topic::find($id);
        if (!$topic) return response()->json(['status' => false, 'message' => 'Not found'], 404);

        $topic->name = $request->name;
        if ($request->name !== $topic->name) {
            $topic->slug = Str::slug($request->name);
        }
        $topic->sort_order = $request->sort_order ?? $topic->sort_order;
        $topic->description = $request->description;
        $topic->status = $request->status ?? $topic->status;
        $topic->updated_at = now();
        $topic->save();

        return response()->json(['status' => true, 'message' => 'Cập nhật thành công', 'topic' => $topic]);
    }

    // 5. Xóa Topic
    public function destroy($id)
    {
        $topic = Topic::find($id);
        if (!$topic) return response()->json(['status' => false, 'message' => 'Not found'], 404);
        
        if ($topic->posts()->count() > 0) {
             return response()->json(['status' => false, 'message' => 'Không thể xóa chủ đề đang chứa bài viết'], 400);
        }

        $topic->delete();
        return response()->json(['status' => true, 'message' => 'Xóa thành công']);
    }
}
