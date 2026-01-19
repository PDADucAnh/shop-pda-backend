<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Đăng ký
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|unique:users',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'username' => $request->email,
            'password' => Hash::make($request->password),
            'roles' => 'customer',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đăng ký thành công',
            'user' => $user
        ], 201);
    }

    // Đăng nhập (ĐÃ FIX LỖI)
    public function login(Request $request)
    {
        // 1. Validate dữ liệu trước tiên để tránh lỗi hệ thống
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Vui lòng nhập Email và Mật khẩu',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Lấy credentials an toàn
        $credentials = $request->only('email', 'password');

        // 3. Thực hiện xác thực
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'status' => false,
                'error' => 'Tài khoản hoặc mật khẩu không đúng'
            ], 401);
        }

        return $this->respondWithToken($token);
    }
// [THÊM MỚI] Hàm cập nhật thông tin cá nhân
    public function updateProfile(Request $request)
    {
        // 1. Lấy user đang đăng nhập
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // 2. Validate dữ liệu gửi lên
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20', // Phone có thể null hoặc chuỗi
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        // 3. Cập nhật dữ liệu
        // Lưu ý: Không cho phép update email ở đây để đảm bảo an toàn
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        // 4. Trả về kết quả
        return response()->json([
            'status' => true,
            'message' => 'Cập nhật thông tin thành công',
            'data' => $user // Trả về user mới để frontend cập nhật store
        ]);
    }
    // Lấy thông tin User
    public function profile()
    {
        return response()->json(Auth::guard('api')->user());
    }

    // Đăng xuất
    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json(['status' => true, 'message' => 'Đăng xuất thành công']);
    }

    // Cấu trúc trả về Token
    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => true, // Thêm trạng thái
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => Auth::guard('api')->user()
        ]);
    }
}