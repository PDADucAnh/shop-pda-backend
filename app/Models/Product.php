<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute; // Thêm dòng này
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'thumbnail',
        'content',
        'description',
        'price_buy',
        'created_by',
        'updated_by',
        'status',
        'is_new',
        'is_sale'
    ];

    protected $appends = ['thumbnail_url', 'sale_price', 'is_actually_new'];

    // --- 1. QUAN HỆ ACTIVE SALE (Tối ưu để eager load) ---
    public function activeSale()
    {
        return $this->hasOne(ProductSale::class, 'product_id', 'id')
            ->where('status', 1)
            ->whereDate('date_begin', '<=', now())
            ->whereDate('date_end', '>=', now())
            ->orderBy('price_sale', 'asc'); // Lấy giá tốt nhất
    }

    // --- 2. ACCESSOR CHO SALE_PRICE (Sửa lại logic này) ---
    protected function salePrice(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Kiểm tra xem quan hệ activeSale đã được load chưa
                if ($this->relationLoaded('activeSale')) {
                    return $this->activeSale ? $this->activeSale->price_sale : null;
                }

                // Fallback: Nếu chưa load, query trực tiếp (nhưng nên tránh)
                $sale = $this->activeSale()->first();
                return $sale ? $sale->price_sale : null;
            }
        );
    }

    // --- Logic is_actually_new (Giữ nguyên) ---
    public function getIsActuallyNewAttribute()
    {
        $isManuallyMarked = $this->is_new == 1;
        $isRecentlyCreated = $this->created_at >= now()->subDays(14);
        return $isManuallyMarked || $isRecentlyCreated;
    }

    // --- Thumbnail URL (Giữ nguyên) ---
    public function getThumbnailUrlAttribute()
    {
        if (!$this->thumbnail)
            return 'https://placehold.co/400x600?text=No+Image';
        if (str_starts_with($this->thumbnail, 'http'))
            return $this->thumbnail;
        return asset('storage/' . $this->thumbnail);
    }

    // --- Relations (Giữ nguyên) ---
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
    public function store()
    {
        return $this->hasOne(ProductStore::class, 'product_id', 'id');
    }
    public function product_attributes()
    {
        return $this->hasMany(ProductAttribute::class, 'product_id', 'id');
    }
    public function sales()
    {
        return $this->hasMany(ProductSale::class, 'product_id', 'id');
    }
}