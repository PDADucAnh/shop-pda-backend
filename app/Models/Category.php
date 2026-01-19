<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    //
    use HasFactory;
    protected $table = 'categories';
    protected $fillable = ['name', 'slug', 'image', 'parent_id', 'sort_order', 'description', 'status'];

    // Quan hệ: Một danh mục có nhiều sản phẩm
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }
}
