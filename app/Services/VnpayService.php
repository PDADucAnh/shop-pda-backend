<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class VnpayService
{
    protected $vnp_TmnCode;
    protected $vnp_HashSecret;
    protected $vnp_Url;
    protected $vnp_ReturnUrl;

    public function __construct()
    {
        // Lấy từ config/services.php để dễ quản lý
        $this->vnp_TmnCode = config('services.vnpay.tmn_code');
        $this->vnp_HashSecret = config('services.vnpay.hash_secret');
        $this->vnp_Url = config('services.vnpay.url');
        $this->vnp_ReturnUrl = config('services.vnpay.return_url');
    }

    public function createPayment($order)
    {
        // Logic như bạn đã cung cấp
        $vnp_TxnRef = $order->id;
        $vnp_Amount = (int) $order->total_money * 100;
        $vnp_Locale = 'vn';
        $vnp_IpAddr = request()->ip() ?: '127.0.0.1';

        date_default_timezone_set('Asia/Ho_Chi_Minh');
        
        $vnp_CreateDate = date('YmdHis');
        $vnp_ExpireDate = date('YmdHis', strtotime('+15 minutes'));

        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_TmnCode"    => $this->vnp_TmnCode,
            "vnp_Amount"     => $vnp_Amount,
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_CurrCode"   => "VND",
            "vnp_IpAddr"     => $vnp_IpAddr,
            "vnp_Locale"     => $vnp_Locale,
            "vnp_OrderInfo"  => "Thanh toan don hang " . $vnp_TxnRef,
            "vnp_OrderType"  => "other",
            "vnp_ReturnUrl"  => $this->vnp_ReturnUrl,
            "vnp_TxnRef"     => $vnp_TxnRef,
            "vnp_ExpireDate" => $vnp_ExpireDate,
        ];

        ksort($inputData);

        $query = "";
        $hashdata = "";
        $i = 0;

        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $this->vnp_Url . "?" .  $query;
        if (isset($this->vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        Log::info('VNPay Request:', [
            'orderId' => $vnp_TxnRef,
            'url' => $vnp_Url
        ]);

        return [
            'status' => true,
            'payUrl' => $vnp_Url,
            'orderId' => $vnp_TxnRef,
        ];
    }

    public function verifyPayment($vnpData)
    {
        $vnp_SecureHash = $vnpData['vnp_SecureHash'] ?? '';
        
        // Loại bỏ các trường hash để tính toán lại
        if (isset($vnpData['vnp_SecureHashType'])) {
            unset($vnpData['vnp_SecureHashType']);
        }
        if (isset($vnpData['vnp_SecureHash'])) {
            unset($vnpData['vnp_SecureHash']);
        }

        ksort($vnpData);

        $hashData = "";
        $i = 0;
        foreach ($vnpData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $this->vnp_HashSecret);
        $isValid = hash_equals($secureHash, $vnp_SecureHash);

        Log::info('VNPay Verify:', [
            'isValid' => $isValid,
            'responseCode' => $vnpData['vnp_ResponseCode'] ?? 'N/A',
            'txnRef' => $vnpData['vnp_TxnRef'] ?? 'N/A',
        ]);

        return [
            'isValid' => $isValid,
            'responseCode' => $vnpData['vnp_ResponseCode'] ?? null,
            'transactionNo' => $vnpData['vnp_TransactionNo'] ?? null,
            'txnRef' => $vnpData['vnp_TxnRef'] ?? null,
            'amount' => isset($vnpData['vnp_Amount']) ? (int)$vnpData['vnp_Amount'] / 100 : 0,
        ];
    }
}