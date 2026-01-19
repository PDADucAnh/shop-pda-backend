<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class NewPasswordController extends Controller
{
    // 1. Gửi link reset password qua email
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Email không hợp lệ'], 422);
        }

        // Kiểm tra user có tồn tại không (Optional: Laravel Password Broker tự lo, nhưng check tay để custom message)
        
        // Gửi link. Lưu ý: Cần cấu hình MAIL trong .env để hoạt động
        // Link trong email sẽ trỏ về Frontend URL (cần cấu hình ở bước sau)
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['status' => true, 'message' => 'Link đặt lại mật khẩu đã được gửi vào email của bạn!']);
        }

        return response()->json(['status' => false, 'message' => 'Không thể gửi email. Vui lòng thử lại.'], 500);
    }

    // 2. Thực hiện đổi mật khẩu mới
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ], [
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false, 
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        // Logic đổi mật khẩu của Laravel
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['status' => true, 'message' => 'Mật khẩu đã được thay đổi thành công!']);
        }

        return response()->json(['status' => false, 'message' => 'Token không hợp lệ hoặc đã hết hạn.'], 400);
    }
}