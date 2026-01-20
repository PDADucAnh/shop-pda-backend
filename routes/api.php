<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
// Route tạm thời để dọn dẹp hệ thống
Route::get('/debug-cloudinary', function () {
    Artisan::call('config:clear'); // Xóa cache cấu hình
    Artisan::call('cache:clear');  // Xóa cache hệ thống
    
    // Kiểm tra xem Laravel có thấy cấu hình cloud_name không
    return [
        'message' => 'Đã xóa cache cấu hình!',
        'cloudinary_cloud_name' => config('cloudinary.cloud_name'),
        'all_cloudinary_config' => config('cloudinary'),
    ];
});
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ProductStoreController;
use App\Http\Controllers\Api\ProductSaleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\Api\AttributeController;
use App\Http\Controllers\Api\VnPayController;
use App\Http\Controllers\Api\NewPasswordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// =========================================================================
// 1. PUBLIC ROUTES (Không cần authentication)
// =========================================================================

// --- AUTHENTICATION ---
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [App\Http\Controllers\Api\NewPasswordController::class, 'forgotPassword']);
    Route::post('reset-password', [App\Http\Controllers\Api\NewPasswordController::class, 'resetPassword']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::get('profile', [AuthController::class, 'profile'])->middleware('auth:api');
    Route::put('profile', [AuthController::class, 'updateProfile'])->middleware('auth:api');
});

// --- PRODUCTS & CATEGORIES (Customer facing) ---
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);           // List with filters
    Route::get('/{slug}', [ProductController::class, 'show']);      // Detail by slug
});
Route::get('search', [App\Http\Controllers\Api\SearchController::class, 'index']);
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show']);
});

// --- CONTENT & UI ---
Route::prefix('menus')->group(function () {
    Route::get('/', [MenuController::class, 'index']);
    Route::get('/{position}', [MenuController::class, 'getByPosition']);
});

Route::prefix('banners')->group(function () {
    Route::get('/', [BannerController::class, 'index']);
    Route::get('/{position}', [BannerController::class, 'getByPosition']);
});

Route::prefix('posts')->group(function () {
    Route::get('/', [PostController::class, 'index']);
    Route::get('/{slug}', [PostController::class, 'show']);
});

Route::prefix('topics')->group(function () {
    Route::get('/', [TopicController::class, 'index']);
});

// --- CONTACT & SUPPORT ---
Route::post('contact', [ContactController::class, 'store']);

// --- CHECKOUT & PAYMENT ---
Route::prefix('orders')->group(function () {
    Route::post('checkout', [OrderController::class, 'store']); // Guest/User checkout
});

// --- VNPAY PAYMENT GATEWAY ---
Route::prefix('payment')->group(function () {
    Route::post('vnpay/create', [VnPayController::class, 'createPayment']);
    Route::get('vnpay/ipn', [VnPayController::class, 'vnpayIpn']);
    Route::get('vnpay/return', [VnPayController::class, 'checkVnpayOrder']);
});

// =========================================================================
// 2. AUTHENTICATED USER ROUTES
// =========================================================================
Route::middleware(['auth:api'])->group(function () {

    // --- USER ORDERS ---
    Route::prefix('my')->group(function () {
        Route::get('orders', [OrderController::class, 'myOrders']);
        Route::get('orders/{id}', [OrderController::class, 'getUserOrderById']);
        Route::post('orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
        // Route::get('profile', [AuthController::class, 'profile']);
    });
});

// =========================================================================
// 3. ADMIN ROUTES (Sử dụng middleware auth:api thay vì admin)
// =========================================================================
Route::middleware(['auth:api'])->prefix('admin')->group(function () {

    // --- PRODUCT MANAGEMENT ---
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    Route::prefix('products')->group(function () {
        Route::post('import', [ProductController::class, 'import']);
        Route::patch('{id}/toggle-status', [ProductController::class, 'toggleStatus']);
    });

    // --- CATEGORY MANAGEMENT ---
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);

    // --- ORDER MANAGEMENT ---
    Route::apiResource('orders', OrderController::class)->except(['store']);
    Route::prefix('orders')->group(function () {
        Route::put('{id}/status', [OrderController::class, 'updateStatus']);
        Route::get('stats', [OrderController::class, 'stats']);
    });

    // --- INVENTORY MANAGEMENT ---
    Route::apiResource('inventory', ProductStoreController::class);
    Route::prefix('inventory')->group(function () {
        Route::post('import', [ProductStoreController::class, 'import']);
        Route::get('low-stock', [ProductStoreController::class, 'lowStock']);
    });

    // --- PROMOTION MANAGEMENT ---
    Route::apiResource('promotions', ProductSaleController::class);
    Route::prefix('promotions')->group(function () {
        Route::post('import', [ProductSaleController::class, 'import']);
        Route::post('batch', [ProductSaleController::class, 'storeBatch']);
        Route::get('active', [ProductSaleController::class, 'activePromotions']);
    });

    // --- USER MANAGEMENT ---
    Route::apiResource('users', UserController::class);
    Route::prefix('users')->group(function () {
        Route::patch('{id}/status', [UserController::class, 'updateStatus']);
        Route::post('{id}/reset-password', [UserController::class, 'resetPassword']);
    });

    // --- CONTENT MANAGEMENT ---
    Route::apiResource('posts', PostController::class)->except(['index', 'show']);
    Route::apiResource('topics', TopicController::class)->except(['index']);

    // --- MENU MANAGEMENT ---
    Route::apiResource('menus', MenuController::class)->except(['index']);

    // --- BANNER MANAGEMENT ---
    Route::apiResource('banners', BannerController::class)->except(['index']);

    // --- ATTRIBUTE MANAGEMENT ---
    Route::apiResource('attributes', AttributeController::class);

    // --- CONTACT MANAGEMENT ---
    Route::apiResource('contacts', ContactController::class)->only(['index', 'show', 'destroy']);
    Route::prefix('contacts')->group(function () {
        Route::post('{id}/reply', [ContactController::class, 'reply']);
        Route::patch('{id}/status', [ContactController::class, 'updateStatus']);
    });
});

// =========================================================================
// 4. COMPATIBILITY ROUTES (Giữ nguyên các routes cũ để không break frontend)
// =========================================================================

// --- PUBLIC DATA (SẢN PHẨM & DANH MỤC) ---
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{slug}', [ProductController::class, 'show']);

// --- QUẢN LÝ DANH MỤC ---
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{id}', [CategoryController::class, 'show']);
Route::post('categories', [CategoryController::class, 'store']);
Route::post('categories/{id}', [CategoryController::class, 'update']);
Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

// --- UI DATA (MENU & BANNER) ---
Route::get('menus/{position?}', [MenuController::class, 'index']);
Route::get('banners/{position?}', [BannerController::class, 'index']);

// --- CMS DATA (BÀI VIẾT & LIÊN HỆ) ---
Route::get('posts', [PostController::class, 'index']);
Route::get('posts/{slug}', [PostController::class, 'show']);
Route::post('contact', [ContactController::class, 'store']);

// --- ORDER (ĐẶT HÀNG) ---
Route::post('checkout', [OrderController::class, 'store']);

// --- VNPAY PAYMENT ---
Route::post('/vnpay/create-payment', [VnPayController::class, 'createPayment']);
Route::get('/vnpay/ipn', [VnPayController::class, 'vnpayIpn']);
Route::get('/vnpay/check-return', [VnPayController::class, 'checkVnpayOrder']);

// --- QUẢN LÝ ĐƠN HÀNG (ADMIN) ---
Route::get('orders', [OrderController::class, 'index']);
Route::get('orders/{id}', [OrderController::class, 'show']);
Route::put('orders/{id}', [OrderController::class, 'update']);
Route::delete('orders/{id}', [OrderController::class, 'destroy']);

// --- QUẢN LÝ SẢN PHẨM (ADMIN) ---
Route::post('products', [ProductController::class, 'store']);
Route::put('products/{id}', [ProductController::class, 'update']);
Route::delete('products/{id}', [ProductController::class, 'destroy']);
Route::post('products/import', [ProductController::class, 'import']);
Route::patch('/products/{id}/toggle-status', [ProductController::class, 'toggleStatus']);

// --- QUẢN LÝ KHO (INVENTORY) ---
Route::get('product-stores', [ProductStoreController::class, 'index']);
Route::post('product-stores/import', [ProductStoreController::class, 'import']);
Route::put('product-stores/{id}', [ProductStoreController::class, 'update']);
Route::delete('product-stores/{id}', [ProductStoreController::class, 'destroy']);

// --- QUẢN LÝ KHUYẾN MÃI (SALES) ---
Route::get('product-sales', [ProductSaleController::class, 'index']);
Route::post('product-sales', [ProductSaleController::class, 'store']);
Route::put('product-sales/{id}', [ProductSaleController::class, 'update']);
Route::delete('product-sales/{id}', [ProductSaleController::class, 'destroy']);
Route::post('product-sales/import', [ProductSaleController::class, 'import']);
Route::post('product-sales/batch', [ProductSaleController::class, 'storeBatch']);

// --- QUẢN LÝ MENU ---
Route::get('menus', [MenuController::class, 'index']);
Route::get('menus/{id}', [MenuController::class, 'show']);
Route::post('menus', [MenuController::class, 'store']);
Route::put('menus/{id}', [MenuController::class, 'update']);
Route::delete('menus/{id}', [MenuController::class, 'destroy']);

// --- QUẢN LÝ CHỦ ĐỀ (TOPIC) ---
Route::get('topics', [TopicController::class, 'index']);
Route::get('topics/{id}', [TopicController::class, 'show']);
Route::post('topics', [TopicController::class, 'store']);
Route::put('topics/{id}', [TopicController::class, 'update']);
Route::delete('topics/{id}', [TopicController::class, 'destroy']);

// --- QUẢN LÝ BÀI VIẾT (POST) ---
Route::get('posts', [PostController::class, 'index']);
Route::get('posts/{id}', [PostController::class, 'show']);
Route::post('posts', [PostController::class, 'store']);
Route::post('posts/{id}', [PostController::class, 'update']);
Route::delete('posts/{id}', [PostController::class, 'destroy']);

// --- QUẢN LÝ BANNER ---
Route::get('banners', [BannerController::class, 'index']);
Route::get('banners/{id}', [BannerController::class, 'show']);
Route::post('banners', [BannerController::class, 'store']);
Route::post('banners/{id}', [BannerController::class, 'update']);
Route::delete('banners/{id}', [BannerController::class, 'destroy']);

// --- THUỘC TÍNH SẢN PHẨM ---
Route::get('attributes', [AttributeController::class, 'index']);

// =========================================================================
// 5. FALLBACK ROUTE
// =========================================================================
Route::fallback(function () {
    return response()->json([
        'status' => false,
        'message' => 'Route not found'
    ], 404);
});

Route::get('/debug/cloudinary', function() {
    return response()->json([
        'environment' => [
            'CLOUDINARY_URL_set' => !empty(env('CLOUDINARY_URL')),
            'CLOUDINARY_CLOUD_NAME' => env('CLOUDINARY_CLOUD_NAME'),
            'CLOUDINARY_API_KEY' => substr(env('CLOUDINARY_API_KEY', ''), 0, 5) . '...',
            'CLOUDINARY_API_SECRET' => !empty(env('CLOUDINARY_API_SECRET')),
        ],
        'config' => [
            'cloudinary.cloud_name' => config('cloudinary.cloud_name'),
            'cloudinary.api_key_exists' => !empty(config('cloudinary.api_key')),
            'cloudinary.upload_preset' => config('cloudinary.upload_preset'),
        ]
    ]);
});

Route::post('/debug/upload-test', function(Request $request) {
    $request->validate(['file' => 'required|image']);
    
    // Tạo file test đơn giản
    $file = $request->file('file');
    
    try {
        // Cách 1: Dùng package
        $upload = Cloudinary::upload($file->getRealPath());
        
        // Cách 2: Dùng API trực tiếp
        /*
        $cloudinary = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => 'dskkphbyf',
                'api_key'    => '592767544744234',
                'api_secret' => '9VUl-XPI8pYDLmO7gSz-_wwXuK4',
            ]
        ]);
        
        $upload = $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => 'test'
        ]);
        */
        
        return response()->json([
            'success' => true,
            'url' => $upload->getSecurePath(),
            'public_id' => $upload->getPublicId(),
            'info' => $upload->getArrayCopy()
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Debug Upload Error: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});