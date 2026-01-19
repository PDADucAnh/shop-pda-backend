<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductStore extends Model
{
    //
    use HasFactory;
    protected $table = 'product_stores';
    protected $fillable = ['product_id', 'price_root', 'qty', 'status'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
