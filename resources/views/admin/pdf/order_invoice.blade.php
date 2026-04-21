<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .invoice-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; }
        .section-title { font-weight: bold; text-transform: uppercase; background: #f2f2f2; padding: 5px; margin-top: 15px; }
        .info-table { width: 100%; margin-top: 10px; }
        .product-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .product-table th, .product-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .text-right { text-align: right; }
        .total-box { margin-top: 20px; float: right; width: 250px; }
        .footer { margin-top: 50px; text-align: center; font-style: italic; }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div class="company-name">QV FASHION</div>
        <h2 style="margin-top: 10px;">HÓA ĐƠN BÁN HÀNG</h2>
        <div>Mã đơn hàng: <strong>{{ $order->order_code }}</strong></div>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <strong>Khách hàng:</strong> {{ $order->user->full_name ?? $order->shipping_recipient_name }}<br>
                <strong>Điện thoại:</strong> {{ $order->shipping_phone_number }}
            </td>
            <td width="50%">
                <strong>Ngày đặt:</strong> {{ $order->created_at->format('d/m/Y H:i') }}<br>
                <strong>Thanh toán:</strong> {{ strtoupper($order->payment_method) }}
            </td>
        </tr>
        <tr>
            <td colspan="2"><strong>Địa chỉ giao hàng:</strong> {{ $order->shipping_address_detail }}, {{ $order->shipping_ward }}, {{ $order->shipping_district }}, {{ $order->shipping_province }}</td>
        </tr>
    </table>

    <table class="product-table">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th class="text-right">Đơn giá</th>
                <th class="text-right">SL</th>
                <th class="text-right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->details as $item)
            <tr>
                <td>{{ $item->product_name }}<br><small>{{ $item->variant_info }}</small></td>
                <td class="text-right">{{ number_format($item->unit_price) }}đ</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->sub_total) }}đ</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <table width="100%">
            <tr>
                <td>Tạm tính:</td>
                <td class="text-right">{{ number_format($order->total_cost) }}đ</td>
            </tr>
            <tr>
                <td>Phí vận chuyển:</td>
                <td class="text-right">+{{ number_format($order->shipping_fee) }}đ</td>
            </tr>
            @if($order->discount_amount > 0)
            <tr>
                <td>Voucher giảm:</td>
                <td class="text-right">-{{ number_format($order->discount_amount) }}đ</td>
            </tr>
            @endif
            <tr style="font-weight: bold; color: red; font-size: 14px;">
                <td>TỔNG CỘNG:</td>
                <td class="text-right">{{ number_format($order->final_amount) }}đ</td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>
    <div class="footer">
        <p>Cảm ơn quý khách đã tin tưởng lựa chọn QVFashion!</p>
        <p>Vui lòng kiểm tra hàng kỹ trước khi nhận!</p>
    </div>
</body>
</html>