<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    // 1. Lấy danh sách bài viết
    public function index(Request $request)
    {
        $query = Post::with('topic')->orderBy('created_at', 'desc');

        if ($request->has('topic_id')) {
            $query->where('topic_id', $request->topic_id);
        }

        if ($request->has('type')) {
            $query->where('post_type', $request->type); // post hoặc page
        }

        $posts = $query->paginate(10);

        // Map thêm image_url
        $posts->getCollection()->transform(function ($post) {
            $post->image_url = $post->image ? (str_starts_with($post->image, 'http') ? $post->image : asset('storage/' . $post->image)) : null;
            return $post;
        });

        return response()->json(['status' => true, 'posts' => $posts]);
    }

    // 2. Chi tiết bài viết
    public function show($id)
    {
        $post = Post::with('topic')->find($id);
        if (!$post) return response()->json(['status' => false, 'message' => 'Not found'], 404);
        
        $post->image_url = $post->image ? (str_starts_with($post->image, 'http') ? $post->image : asset('storage/' . $post->image)) : null;
        
        return response()->json(['status' => true, 'post' => $post]);
    }

    // 3. Thêm mới bài viết
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|unique:posts,title',
            'topic_id' => 'nullable|exists:topics,id',
            'content' => 'required',
            'post_type' => 'required|in:post,page',
            'image' => 'nullable|image|max:2048'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        $post = new Post();
        $post->topic_id = $request->topic_id;
        $post->title = $request->title;
        $post->slug = Str::slug($request->title);
        $post->content = $request->input('content');
        $post->description = $request->description;
        $post->image = $imagePath;
        $post->post_type = $request->post_type;
        $post->status = $request->status ?? 1;
        $post->created_by = auth('api')->id() ?? 1;
        $post->created_at = now();
        $post->save();

        return response()->json(['status' => true, 'message' => 'Thêm bài viết thành công', 'post' => $post]);
    }

    // 4. Cập nhật bài viết
    public function update(Request $request, $id)
    {
        $post = Post::find($id);
        if (!$post) return response()->json(['status' => false, 'message' => 'Not found'], 404);

        $request->validate([
            'title' => 'required|unique:posts,title,' . $id,
        ]);

        $imagePath = $post->image;
        if ($request->hasFile('image')) {
             if ($post->image && Storage::disk('public')->exists($post->image)) {
                Storage::disk('public')->delete($post->image);
            }
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        $post->topic_id = $request->topic_id;
        $post->title = $request->title;
        if ($request->title !== $post->title) {
            $post->slug = Str::slug($request->title);
        }
        $post->content = $request->input('content'); // Fix lỗi protected content
        $post->description = $request->description;
        $post->image = $imagePath;
        $post->post_type = $request->post_type;
        $post->status = $request->status ?? $post->status;
        $post->updated_by = auth('api')->id() ?? 1;
        $post->updated_at = now();
        $post->save();

        return response()->json(['status' => true, 'message' => 'Cập nhật thành công', 'post' => $post]);
    }

    // 5. Xóa bài viết
    public function destroy($id)
    {
        $post = Post::find($id);
        if (!$post) return response()->json(['status' => false, 'message' => 'Not found'], 404);

        if ($post->image && Storage::disk('public')->exists($post->image)) {
            Storage::disk('public')->delete($post->image);
        }
        
        $post->delete();
        return response()->json(['status' => true, 'message' => 'Xóa thành công']);
    }
}
