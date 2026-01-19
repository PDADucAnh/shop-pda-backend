<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderSuccess;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductStore;
use App\Services\VnpayService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    protected VnpayService $vnpayService;

    public function __construct(VnpayService $vnpayService)
    {
        $this->vnpayService = $vnpayService;
    }

    // =========================================================================
    // 1. TẠO YÊU CẦU ĐẶT HÀNG (STORE)
    // =========================================================================
    public function store(Request $request)
    {
        // 1. Auth: Lấy User ID hoặc gán 1 (Guest)
        $user = $request->user('api');
        $userId = $user ? $user->id : 1;

        // 2. Validate
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|integer|exists:products,id',
            'details.*.qty' => 'required|integer|min:1',
            'details.*.price' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // Tính tổng tiền & Check kho
            $totalMoney = 0;
            foreach ($request->details as $item) {
                $stock = ProductStore::where('product_id', $item['product_id'])->sum('qty');
                if ($stock < $item['qty']) {
                    return response()->json([
                        'status' => false,
                        'message' => "Sản phẩm ID {$item['product_id']} không đủ hàng (còn {$stock})",
                    ], 400);
                }
                $discount = $item['discount'] ?? 0;
                $totalMoney += ($item['qty'] * $item['price']) - $discount;
            }

            $totalMoney = (int) round($totalMoney);
            $paymentMethod = $request->payment_method ?? 'cod';

            // --- CASE 1: VNPAY ---
            if ($paymentMethod === 'vnpay') {
                if ($totalMoney < 10000) {
                    return response()->json(['status' => false, 'message' => 'VNPAY yêu cầu tối thiểu 10.000đ'], 400);
                }

                $tempOrderId = 'VNP_' . time() . '_' . rand(1000, 9999);

                $orderData = [
                    'user_id' => $userId,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'note' => $request->note ?? null,
                    'details' => $request->details,
                    'total_money' => $totalMoney,
                    'payment_method' => 'vnpay',
                ];
                Cache::put($tempOrderId, $orderData, now()->addMinutes(30));

                $dummyOrder = (object) ['id' => $tempOrderId, 'total_money' => $totalMoney];
                $vnpayResponse = $this->vnpayService->createPayment($dummyOrder);

                if (isset($vnpayResponse['payUrl'])) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Redirecting to VNPAY...',
                        'payment_url' => $vnpayResponse['payUrl'],
                        'orderId' => $tempOrderId,
                    ], 200);
                }
                return response()->json(['status' => false, 'message' => 'Lỗi tạo URL VNPAY'], 500);
            }

            // --- CASE 2: COD ---
            DB::beginTransaction();
            $order = $this->createOrderRecord($request->all(), $totalMoney, 'cod', $userId);
            $this->deductStock($order);
            DB::commit();

            try {
                $this->sendOrderConfirmationEmail($order);
            } catch (Exception $e) {
                Log::error("Mail Error: " . $e->getMessage());
            }

            return response()->json([
                'status' => true,
                'message' => 'Đặt hàng thành công!',
                'order_id' => $order->id,
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Order Store Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 2. VNPAY IPN
    // =========================================================================
    public function vnpayIpn(Request $request)
    {
        try {
            $result = $this->vnpayService->verifyPayment($request->all());
            if (!$result['isValid'])
                return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);

            $tempOrderId = $result['txnRef'];
            if ($result['responseCode'] == '00') {
                $orderData = Cache::get($tempOrderId);
                if (!$orderData) {
                    $exists = Order::where('note', 'LIKE', "%{$tempOrderId}%")->exists();
                    return response()->json(['RspCode' => $exists ? '00' : '01', 'Message' => $exists ? 'Confirm Success' : 'Order not found']);
                }

                DB::beginTransaction();
                try {
                    $order = $this->createOrderRecord($orderData, $orderData['total_money'], 'vnpay', $orderData['user_id']);
                    $order->status = 2; // Đã thanh toán
                    $order->note .= " | VNPAY: {$tempOrderId}";
                    $order->save();

                    $this->deductStock($order);
                    DB::commit();

                    $this->sendOrderConfirmationEmail($order);
                    Cache::forget($tempOrderId);

                    return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
                } catch (Exception $e) {
                    DB::rollBack();
                    return response()->json(['RspCode' => '99', 'Message' => 'Database Error']);
                }
            }
            return response()->json(['RspCode' => '00', 'Message' => 'Payment Failed']);
        } catch (Exception $e) {
            return response()->json(['RspCode' => '99', 'Message' => 'Unknown Error']);
        }
    }

    // =========================================================================
    // 3. VNPAY RETURN
    // =========================================================================
    public function checkVnpayOrder(Request $request)
    {
        $result = $this->vnpayService->verifyPayment($request->all());
        if (!$result['isValid'])
            return response()->json(['status' => false, 'message' => 'Chữ ký không hợp lệ'], 400);

        if ($result['responseCode'] != '00') {
            return response()->json(['status' => false, 'message' => 'Thanh toán thất bại hoặc bị hủy'], 400);
        }

        $tempOrderId = $result['txnRef'];

        // Check DB trước
        $existingOrder = Order::where('note', 'LIKE', "%{$tempOrderId}%")->first();
        if ($existingOrder) {
            return response()->json(['status' => true, 'order_id' => $existingOrder->id], 200);
        }

        // Check Cache
        $orderData = Cache::get($tempOrderId);
        if (!$orderData)
            return response()->json(['status' => false, 'message' => 'Không tìm thấy đơn hàng'], 404);

        DB::beginTransaction();
        try {
            $order = $this->createOrderRecord($orderData, $orderData['total_money'], 'vnpay', $orderData['user_id']);
            $order->status = 2;
            $order->note .= " | VNPAY: {$tempOrderId}";
            $order->save();

            $this->deductStock($order);
            DB::commit();

            $this->sendOrderConfirmationEmail($order);
            Cache::forget($tempOrderId);

            return response()->json(['status' => true, 'order_id' => $order->id], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function createOrderRecord($data, $totalMoney, $method, $userId = 1)
    {
        $order = Order::create([
            'user_id' => $userId ?: 1,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'note' => $data['note'] ?? '',
            'status' => 1,
            'total_money' => $totalMoney,
            'created_by' => $userId ?: 1,
            'payment_method' => $method,
        ]);

        foreach ($data['details'] as $item) {
            $qty = $item['qty'];
            $price = $item['price'];
            $amount = $qty * $price;

            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'qty' => $qty,
                'price' => $price,
                'amount' => $amount,
            ]);
        }

        return $order;
    }

    private function deductStock($order)
    {
        foreach ($order->order_details as $detail) {
            $qtyNeed = $detail->qty;
            $batches = ProductStore::where('product_id', $detail->product_id)
                ->where('qty', '>', 0)
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($qtyNeed <= 0)
                    break;
                if ($batch->qty >= $qtyNeed) {
                    $batch->qty -= $qtyNeed;
                    $qtyNeed = 0;
                } else {
                    $qtyNeed -= $batch->qty;
                    $batch->qty = 0;
                }
                $batch->save();
            }

            // Cập nhật trạng thái hết hàng
            $totalStock = ProductStore::where('product_id', $detail->product_id)->sum('qty');
            if ($totalStock <= 0) {
                Product::where('id', $detail->product_id)->update(['status' => 0]);
            }
        }
    }

    private function sendOrderConfirmationEmail($order)
    {
        if (!empty($order->email)) {
            $order->load('order_details.product');
            Mail::to($order->email)->send(new OrderSuccess($order));
        }
    }

    // =========================================================================
    // PUBLIC API METHODS
    // =========================================================================

    /**
     * Get all orders (Admin)
     */
    public function index(Request $request)
    {
        $query = Order::with('order_details.product');

        // Filter logic
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('phone', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%');
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate($request->limit ?? 10);

        // Tính total_money cho mỗi order nếu chưa có
        $orders->getCollection()->transform(function ($order) {
            // Nếu chưa có total_money trong DB, tính từ order_details
            if (!$order->total_money && $order->order_details) {
                $order->total_money = $order->order_details->sum('amount');
            }
            // Mặc định payment_method là 'cod' nếu chưa có
            $order->payment_method = $order->payment_method ?? 'cod';
            return $order;
        });

        return response()->json([
            'status' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get order by ID (Admin/Public)
     */
    public function show($id)
    {
        $order = Order::with('order_details.product')->find($id);
        return $order
            ? response()->json(['status' => true, 'data' => $order])
            : response()->json(['status' => false, 'message' => 'Not found'], 404);
    }

    /**
     * Update order status (Admin)
     */
    public function update(Request $request, $id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không tìm thấy đơn hàng'
                ], 404);
            }

            // Validate chỉ cho phép cập nhật status
            $validator = Validator::make($request->all(), [
                'status' => 'required|integer|in:1,2,3,4,5'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Lấy user hiện tại (admin)
            $user = $request->user('api');
            $userId = $user ? $user->id : 1;

            // Cập nhật trạng thái
            $order->status = $request->status;
            $order->updated_by = $userId;
            $order->save();

            // Log activity
            Log::info("Order {$order->id} updated to status {$order->status} by user {$userId}");

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật trạng thái thành công!',
                'data' => $order
            ], 200);

        } catch (Exception $e) {
            Log::error('Order Update Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete order (Admin)
     */
    public function destroy($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không tìm thấy đơn hàng'
                ], 404);
            }

            // Chỉ cho phép xóa đơn hàng ở trạng thái hủy (5) hoặc mới (1)
            if ($order->status != 5 && $order->status != 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Chỉ có thể xóa đơn hàng ở trạng thái "Mới" hoặc "Đã hủy"'
                ], 400);
            }

            // Xóa order_details trước
            OrderDetail::where('order_id', $id)->delete();

            // Xóa order
            $order->delete();

            Log::info("Order {$id} deleted");

            return response()->json([
                'status' => true,
                'message' => 'Đã xóa đơn hàng thành công!'
            ], 200);

        } catch (Exception $e) {
            Log::error('Order Delete Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // USER ORDER METHODS (Authenticated User)
    // =========================================================================

    /**
     * Get orders for authenticated user
     */
    public function myOrders(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $orders = Order::where('user_id', $user->id)
            ->with('order_details.product')
            ->orderBy('created_at', 'desc')
            ->get();

        // Tính total_money và payment_method cho mỗi order
        $orders->transform(function ($order) {
            if (!$order->total_money && $order->order_details) {
                $order->total_money = $order->order_details->sum('amount');
            }
            $order->payment_method = $order->payment_method ?? 'cod';
            return $order;
        });

        return response()->json([
            'status' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get order detail for authenticated user
     */
    public function getUserOrderById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Lấy đơn hàng của user hiện tại
        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['order_details.product'])
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found or access denied'
            ], 404);
        }

        // Tính total_money nếu chưa có
        if (!$order->total_money && $order->order_details) {
            $order->total_money = $order->order_details->sum('amount');
        }
        $order->payment_method = $order->payment_method ?? 'cod';

        return response()->json([
            'status' => true,
            'data' => $order
        ]);
    }

    /**
     * Cancel order (Authenticated User)
     */
    public function cancelOrder(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Chỉ cho phép hủy đơn hàng ở trạng thái mới (1)
        if ($order->status != 1) {
            return response()->json([
                'status' => false,
                'message' => 'Chỉ có thể hủy đơn hàng ở trạng thái "Mới"'
            ], 400);
        }

        // Cập nhật trạng thái hủy (5)
        $order->status = 5;
        $order->save();

        // Hoàn lại số lượng tồn kho
        $this->restoreStock($order);

        Log::info("Order {$order->id} cancelled by user {$user->id}");

        return response()->json([
            'status' => true,
            'message' => 'Đã hủy đơn hàng thành công!'
        ]);
    }

    /**
     * Restore stock when order is cancelled
     */
    private function restoreStock($order)
    {
        foreach ($order->order_details as $detail) {
            // Tìm batch gần nhất để hoàn lại stock
            $batch = ProductStore::where('product_id', $detail->product_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($batch) {
                $batch->qty += $detail->qty;
                $batch->save();
            }

            // Cập nhật trạng thái sản phẩm nếu cần
            $totalStock = ProductStore::where('product_id', $detail->product_id)->sum('qty');
            if ($totalStock > 0) {
                Product::where('id', $detail->product_id)->update(['status' => 1]);
            }
        }
    }
}