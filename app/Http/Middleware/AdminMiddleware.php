<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Kiểm tra role admin (tùy vào cấu trúc database của bạn)
        // Giả sử user có field 'role' với giá trị 'admin'
        if ($user->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Access denied. Admin only.'
            ], 403);
        }

        return $next($request);
    }
}