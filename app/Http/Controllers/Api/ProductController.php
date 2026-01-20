<?php

namespace App\Http\Controllers\Api;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductAttribute;
use App\Models\Category;
use App\Models\ProductStore;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\Csv\Reader as CsvReader;
use OpenSpout\Reader\Xlsx\Reader as XlsxReader;

class ProductController extends Controller
{
    // Lấy danh sách sản phẩm (Giữ nguyên)
    public function index(Request $request)
    {
        $query = Product::with(['images', 'sales']);
        $query = Product::with(['images', 'activeSale']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('is_new')) {
            $query->where('is_new', 1);
        }
        if ($request->has('is_sale')) {
            $query->where('is_sale', 1);
        }
        if ($request->has('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }
        if ($request->has('sort')) {
            if ($request->sort == 'price_asc')
                $query->orderBy('price_buy', 'asc');
            if ($request->sort == 'price_desc')
                $query->orderBy('price_buy', 'desc');
            if ($request->sort == 'newest')
                $query->orderBy('created_at', 'desc');
        }
        if ($request->has('price_min') && $request->has('price_max')) {
            $query->whereBetween('price_buy', [$request->price_min, $request->price_max]);
        }
        $products = $query->paginate(12);

        return response()->json([
            'status' => true,
            'message' => 'Tải danh sách sản phẩm thành công',
            'products' => $products
        ]);
    }

    // Chi tiết sản phẩm (Giữ nguyên)
    public function show($id_or_slug)
    {
        $product = Product::where('id', $id_or_slug)
            ->orWhere('slug', $id_or_slug)
            ->with(['images', 'store', 'product_attributes', 'category', 'activeSale'])
            ->first();

        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy sản phẩm'], 404);
        }

        $related_products = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return response()->json([
            'status' => true,
            'product' => $product,
            'related_products' => $related_products
        ]);
    }

    // Tạo mới sản phẩm (Giữ nguyên)
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255|unique:products,name',
        'price_buy' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'thumbnail' => 'required|image|max:5120',
        'content' => 'required',
        'gallery.*' => 'image|max:5120',
    ]);

    DB::beginTransaction();
    try {
        // UPLOAD THUMBNAIL
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            // Cách 1: Dùng upload preset
            $uploadedFile = Cloudinary::upload($request->file('thumbnail')->getRealPath(), [
                'upload_preset' => config('cloudinary.upload_preset', 'ml_default'),
                'folder' => 'products'
            ]);
            
            // Cách 2: Hoặc upload đơn giản
            // $uploadedFile = Cloudinary::upload($request->file('thumbnail')->getRealPath());
            
            $thumbnailPath = $uploadedFile->getSecurePath();
            
            \Log::info('Thumbnail uploaded: ' . $thumbnailPath);
        }

        $product = Product::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . time(),
            'category_id' => $request->category_id,
            'price_buy' => $request->price_buy,
            'content' => $request->input('content'),
            'description' => $request->description,
            'thumbnail' => $thumbnailPath,
            'status' => $request->has('status') ? $request->status : 1,
            'is_new' => $request->status_new ?? 1,
            'created_by' => auth('api')->id() ?? 1,
        ]);

        // UPLOAD GALLERY IMAGES
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $index => $file) {
                $uploaded = Cloudinary::upload($file->getRealPath(), [
                    'upload_preset' => config('cloudinary.upload_preset', 'ml_default'),
                    'folder' => 'products/gallery'
                ]);
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $uploaded->getSecurePath(),
                    'alt' => $product->name,
                ]);
            }
        }

        DB::commit();
        return response()->json([
            'status' => true,
            'message' => 'Thêm sản phẩm thành công',
            'product' => $product
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Product Store Error: ' . $e->getMessage());
        \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
        
        return response()->json([
            'status' => false,
            'message' => 'Lỗi khi upload ảnh lên Cloudinary',
            'error' => $e->getMessage()
        ], 500);
    }
}
    // --- CẬP NHẬT SẢN PHẨM (ĐÃ SỬA ĐỂ HỖ TRỢ XÓA ẢNH & THÊM ẢNH) ---
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy sản phẩm'], 404);
        }

        // 1. Validate
        $request->validate([
            'name' => 'required|string|max:255|unique:products,name,' . $id,
            'price_buy' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'content' => 'required',
            'thumbnail' => 'nullable|image|max:5120',
            'gallery.*' => 'image|max:5120',
            // Thêm validate cho mảng ảnh cần xóa (nếu có)
            'deleted_images' => 'nullable|array',
            'deleted_images.*' => 'integer'
        ]);

        DB::beginTransaction();
        try {
            // 2. Handle Thumbnail (Replace if new one uploaded)
            $thumbnailPath = $product->thumbnail;
            if ($request->hasFile('thumbnail')) {
                if ($product->thumbnail && str_contains($product->thumbnail, 'cloudinary')) {
                    $publicId = basename($product->thumbnail, '.' . pathinfo($product->thumbnail, PATHINFO_EXTENSION));
                    Cloudinary::destroy('products/' . $publicId);
                }
                $thumbnailPath = Cloudinary::upload($request->file('thumbnail')->getRealPath(), ['folder' => 'products'])->getSecurePath();
            }

            // 3. Update Basic Info
            $product->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . $product->id,
                'category_id' => $request->category_id,
                'price_buy' => $request->price_buy,
                'content' => $request->input('content'),
                'description' => $request->description,
                'thumbnail' => $thumbnailPath,
                'status' => $request->has('status') ? $request->status : $product->status,
                'is_new' => $request->status_new ?? 1,
                'updated_by' => auth('api')->id() ?? 1,
            ]);

            // 4. Update Attributes
            if ($request->has('attributes')) {
                ProductAttribute::where('product_id', $product->id)->delete();
                $attributes = json_decode($request->input('attributes'), true);
                if (is_array($attributes)) {
                    foreach ($attributes as $attr) {
                        if (!empty($attr['attribute_id']) && !empty($attr['value'])) {
                            ProductAttribute::create([
                                'product_id' => $product->id,
                                'attribute_id' => $attr['attribute_id'],
                                'value' => $attr['value']
                            ]);
                        }
                    }
                }
            }

            // --- 5. LOGIC MỚI: XÓA ẢNH GALLERY CŨ ---
            if ($request->has('deleted_images')) {
                $idsToDelete = $request->input('deleted_images');

                // Tìm các ảnh cần xóa, đảm bảo chúng thuộc về sản phẩm này (để an toàn)
                $imagesToDelete = ProductImage::whereIn('id', $idsToDelete)
                    ->where('product_id', $product->id)
                    ->get();

                foreach ($imagesToDelete as $img) {
                    // Xóa file vật lý trong storage
                    if (Storage::disk('cloudinary')->exists($img->image)) {
                        Storage::disk('cloudinary')->delete($img->image);
                    }
                    // Xóa bản ghi trong database
                    $img->delete();
                }
            }

            // --- 6. LOGIC CŨ: THÊM ẢNH MỚI (APPEND) ---
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $path = $file->store('products/gallery', 'cloudinary');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $path,
                        'alt' => $product->name,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật sản phẩm thành công',
                'product' => $product
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    // Xóa sản phẩm (Giữ nguyên)
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            if ($product->thumbnail && Storage::disk('cloudinary')->exists($product->thumbnail)) {
                Storage::disk('cloudinary')->delete($product->thumbnail);
            }
            $product->delete();

            return response()->json([
                'status' => true,
                'message' => 'Đã xóa sản phẩm thành công'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi khi xóa: ' . $e->getMessage()
            ], 500);
        }
    }

    // Import (Giữ nguyên)
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls'
        ]);

        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $filePath = $file->getPathname();
            $extension = strtolower($file->getClientOriginalExtension());

            if ($extension === 'csv') {
                $reader = new CsvReader();
            } else {
                $reader = new XlsxReader();
            }

            $reader->open($filePath);
            $isHeader = true;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    if ($isHeader) {
                        $isHeader = false;
                        continue;
                    }
                    $cells = $row->toArray();
                    if (count($cells) < 3)
                        continue;

                    $name = $cells[0] ?? 'No Name';
                    $catName = $cells[1] ?? 'Uncategorized';
                    $price = is_numeric($cells[2] ?? 0) ? $cells[2] : 0;
                    $qty = is_numeric($cells[3] ?? 0) ? $cells[3] : 0;
                    $cost = is_numeric($cells[4] ?? 0) ? $cells[4] : ($price * 0.7);
                    $desc = $cells[5] ?? '';
                    $content = $cells[6] ?? '';
                    $thumb = $cells[7] ?? null;

                    $category = Category::firstOrCreate(
                        ['name' => $catName],
                        ['slug' => Str::slug($catName), 'status' => 1]
                    );

                    $product = Product::create([
                        'category_id' => $category->id,
                        'name' => $name,
                        'slug' => Str::slug($name) . '-' . time() . '-' . rand(100, 999),
                        'price_buy' => $price,
                        'content' => $content,
                        'description' => $desc,
                        'thumbnail' => $thumb,
                        'status' => 1,
                        'is_new' => 1,
                        'created_by' => auth('api')->id() ?? 1,
                    ]);

                    ProductStore::create([
                        'product_id' => $product->id,
                        'price_root' => $cost,
                        'qty' => $qty,
                        'status' => 1
                    ]);

                    if ($thumb) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image' => $thumb,
                            'alt' => $name,
                        ]);
                    }
                }
                break;
            }

            $reader->close();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Import successfully via OpenSpout!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Import Failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // Toggle Status (Giữ nguyên)
    public function toggleStatus($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return response()->json(['status' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
            }
            $product->status = !$product->status;
            $product->save();

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'new_status' => $product->status
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

