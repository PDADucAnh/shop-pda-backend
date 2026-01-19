<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    protected $table = 'order_details'; 

    protected $fillable = [
        'order_id', 'product_id', 'price', 'qty', 'amount', 'discount'
    ];
    
    public $timestamps = false; // Kiểm tra xem bảng này có created_at/updated_at không, nếu không thì set false

    // Quan hệ ngược: Chi tiết thuộc về Đơn hàng
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    // Quan hệ: Chi tiết thuộc về Sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
