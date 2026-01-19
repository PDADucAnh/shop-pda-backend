<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attribute extends Model
{
    //
    use HasFactory;
    protected $table = 'attributes';
    protected $fillable = ['name'];
    public $timestamps = false; // Bảng này không có created_at/updated_at

    // Quan hệ: Một thuộc tính có nhiều giá trị ở bảng trung gian
    public function product_attributes()
    {
        return $this->hasMany(ProductAttribute::class, 'attribute_id', 'id');
    }
}
