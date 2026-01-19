<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject; // <--- 1. Import thư viện JWT

class User extends Authenticatable implements JWTSubject // <--- 2. Kế thừa Interface JWT
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // 3. Cập nhật các trường được phép thêm dữ liệu (khớp với Database Migration)
    protected $fillable = [
        'name',
        'email',
        'phone',
        'username',
        'password',
        'roles',
        'avatar',
        'status',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- 4. CÁC HÀM CỦA JWT (BẮT BUỘC) ---

    /**
     * Lấy định danh của user (thường là id) để lưu vào token
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Thêm các thông tin tùy chỉnh vào token (ví dụ role)
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->roles,
            'name' => $this->name
        ];
    }

    // --- 5. QUAN HỆ (RELATIONSHIPS) ---

    // Một User có nhiều đơn hàng
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id', 'id');
    }
}