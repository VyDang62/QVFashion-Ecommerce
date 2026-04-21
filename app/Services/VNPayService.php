<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class VnpayService
{
    protected $tmnCode;
    protected $hashSecret;
    protected $baseUrl;
    protected $apiUrl;

    public function __construct()
    {
        $this->tmnCode = config('services.vnpay.VNP_TMN_CODE');
        $this->hashSecret = config('services.vnpay.VNP_HASH_SECRET');
        $this->baseUrl = config('services.vnpay.VNP_URL');
        $this->apiUrl = config('services.vnpay.VNP_API_URL'); // URL API cho Refund/Query
    }

    public function createPaymentUrl(Order $order)
    {
        $vnp_TxnRef = $order->id;
        $vnp_OrderInfo = "Thanh toan don hang: " . $order->order_code;
        $vnp_Amount = $order->final_amount * 100;

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->tmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => request()->ip(),
            "vnp_Locale" => 'vn',
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => 'billpayment',
            "vnp_ReturnUrl" => route('vnpay.return'),
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

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

        $vnp_Url = $this->baseUrl . "?" . $query;
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->hashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

        return $vnp_Url;
    }

    public function verifyHash(array $inputData)
    {
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
        ksort($inputData);

        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $this->hashSecret);
        return $secureHash === $vnp_SecureHash;
    }

    public function refund(Order $order, $amount = null, $type = '02')
    {
        $payment = Payment::where('order_id', $order->id)->first();
        if (!$payment) return ['status' => false, 'message' => 'Không tìm thấy thông tin giao dịch gốc!'];

        $vnp_RequestId = date("YmdHis"); // Mã yêu cầu duy nhất
        $vnp_Amount = ($amount ?? $order->final_amount) * 100;
        
        $data = [
            "vnp_RequestId"      => $vnp_RequestId,
            "vnp_Version"        => "2.1.0",
            "vnp_Command"        => "refund",
            "vnp_TmnCode"        => $this->tmnCode,
            "vnp_TransactionType"=> $type,
            "vnp_TxnRef"         => $order->id,
            "vnp_Amount"         => $vnp_Amount,
            "vnp_OrderInfo"      => "Hoan tien don hang: " . $order->order_code,
            "vnp_TransactionNo"  => $payment->transaction_no,
            "vnp_TransactionDate"=> $payment->pay_date, // Thời gian thanh toán gốc
            "vnp_CreateBy"       => auth()->user()->full_name ?? 'System',
            "vnp_CreateDate"     => date('YmdHis'),
            "vnp_IpAddr"         => request()->ip(),
        ];

        //Tạo chuỗi Hash
        $hashData = $data['vnp_RequestId'] . '|' . $data['vnp_Version'] . '|' . $data['vnp_Command'] . '|' . $data['vnp_TmnCode'] . '|' . $data['vnp_TransactionType'] . '|' . $data['vnp_TxnRef'] . '|' . $data['vnp_Amount'] . '|' . $data['vnp_TransactionNo'] . '|' . $data['vnp_TransactionDate'] . '|' . $data['vnp_CreateBy'] . '|' . $data['vnp_CreateDate'] . '|' . $data['vnp_IpAddr'] . '|' . $data['vnp_OrderInfo'];
        
        $data['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $this->hashSecret);

        $response = Http::post($this->apiUrl, $data);
        
        return $response->json();
    }
}