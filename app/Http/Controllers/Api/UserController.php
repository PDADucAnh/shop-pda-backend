<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // 1. Lấy danh sách người dùng
    public function index(Request $request)
    {
        $query = User::orderBy('created_at', 'desc');

        // Lọc theo Role
        if ($request->has('role') && $request->role !== 'all') {
            $query->where('roles', $request->role);
        }

        // Tìm kiếm
        if ($request->has('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->keyword . '%')
                  ->orWhere('email', 'like', '%' . $request->keyword . '%')
                  ->orWhere('phone', 'like', '%' . $request->keyword . '%');
            });
        }

        $users = $query->paginate(10); // Phân trang 10 user/trang

        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    // 2. Lấy chi tiết 1 user
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['status' => false, 'message' => 'Không tìm thấy user'], 404);
        return response()->json(['status' => true, 'data' => $user]);
    }

    // 3. Tạo mới User
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'password' => 'required|min:6',
            'roles' => 'required|in:admin,customer',
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'username' => $request->email, // Tạm dùng email làm username
                'password' => Hash::make($request->password),
                'roles' => $request->roles,
                'address' => $request->address,
                'status' => 1
            ]);

            return response()->json(['status' => true, 'message' => 'Thêm thành công', 'data' => $user], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 4. Cập nhật User
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['status' => false, 'message' => 'Không tìm thấy user'], 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)], // Bỏ qua check trùng chính nó
            'phone' => ['required', Rule::unique('users')->ignore($user->id)],
            'roles' => 'required|in:admin,customer',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->roles = $request->roles;
        if ($request->has('address')) $user->address = $request->address;
        
        // Nếu có nhập password mới thì đổi, không thì giữ nguyên
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        // Nếu có upload avatar mới
        if ($request->hasFile('avatar')) {
             // Logic upload ảnh (tương tự Product)
        }

        $user->save();

        return response()->json(['status' => true, 'message' => 'Cập nhật thành công', 'data' => $user]);
    }

    // 5. Xóa User
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['status' => false, 'message' => 'Không tìm thấy user'], 404);
        
        // Không cho phép tự xóa chính mình
        if (auth()->id() == $id) {
            return response()->json(['status' => false, 'message' => 'Bạn không thể xóa chính mình'], 403);
        }

        $user->delete();
        return response()->json(['status' => true, 'message' => 'Xóa thành công']);
    }
}
