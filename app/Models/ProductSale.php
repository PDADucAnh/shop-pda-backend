<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductSale extends Model
{
use HasFactory;
    protected $table = 'product_sales';
    protected $fillable = ['product_id', 'name', 'price_sale', 'date_begin', 'date_end', 'status'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
