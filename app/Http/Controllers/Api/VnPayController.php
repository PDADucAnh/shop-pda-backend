<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order; // Giả sử bạn có model Order

class VnPayController extends Controller
{
    // API 1: Tạo URL thanh toán
    public function createPayment(Request $request)
    {
        // 1. Lấy thông tin đơn hàng từ request hoặc DB
        $vnp_TxnRef = $request->order_id; // Mã đơn hàng (phải unique)
        $vnp_Amount = $request->amount; // Số tiền (VNĐ)
        $vnp_OrderInfo = "Thanh toan don hang " . $vnp_TxnRef;
        $vnp_IpAddr = $request->ip();

        // 2. Cấu hình VNPAY
        $vnp_Url = env('VNP_URL');
        $vnp_Returnurl = env('VNP_RETURN_URL');
        $vnp_TmnCode = env('VNP_TMN_CODE');
        $vnp_HashSecret = env('VNP_HASH_SECRET');

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount * 100, // VNPAY yêu cầu nhân 100
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );

        // Thêm bankCode nếu người dùng chọn ngân hàng cụ thể (Optional)
        if ($request->bankCode) {
            $inputData['vnp_BankCode'] = $request->bankCode;
        }

        // 3. Tạo chuỗi hash và URL
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        // Trả về URL cho Frontend redirect
        return response()->json([
            'status' => true,
            'payment_url' => $vnp_Url
        ]);
    }

    // API 2: IPN (VNPAY gọi vào đây để update DB)
    public function vnpayIpn(Request $request)
    {
        $inputData = array();
        $returnData = array();

        // Lấy tất cả dữ liệu VNPAY gửi sang
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        $orderId = $inputData['vnp_TxnRef'];
        $vnp_Amount = $inputData['vnp_Amount'] / 100; // Chia lại cho 100

        try {
            // 1. Kiểm tra Checksum
            if ($secureHash == $vnp_SecureHash) {

                // 2. Lấy đơn hàng từ DB
                $order = Order::find($orderId); // Hoặc logic tìm đơn của bạn

                if ($order != NULL) {
                    // 3. Kiểm tra số tiền
                    if ($order->total_price == $vnp_Amount) { // Giả sử trường giá là total_price

                        // 4. Kiểm tra trạng thái đơn hàng hiện tại (Chưa thanh toán mới update)
                        if ($order->status == 0) { // Giả sử 0 là chưa thanh toán

                            if ($inputData['vnp_ResponseCode'] == '00' && $inputData['vnp_TransactionStatus'] == '00') {
                                // CẬP NHẬT TRẠNG THÁI THÀNH CÔNG
                                $order->status = 1; // 1: Đã thanh toán
                            } else {
                                // CẬP NHẬT TRẠNG THÁI THẤT BẠI
                                $order->status = 2; // 2: Thất bại
                            }
                            $order->save();

                            $returnData['RspCode'] = '00';
                            $returnData['Message'] = 'Confirm Success';
                        } else {
                            $returnData['RspCode'] = '02';
                            $returnData['Message'] = 'Order already confirmed';
                        }
                    } else {
                        $returnData['RspCode'] = '04';
                        $returnData['Message'] = 'invalid amount';
                    }
                } else {
                    $returnData['RspCode'] = '01';
                    $returnData['Message'] = 'Order not found';
                }
            } else {
                $returnData['RspCode'] = '97';
                $returnData['Message'] = 'Invalid signature';
            }
        } catch (\Exception $e) {
            $returnData['RspCode'] = '99';
            $returnData['Message'] = 'Unknow error';
        }

        return response()->json($returnData);
    }
}