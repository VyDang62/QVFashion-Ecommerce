<table>
    <thead>
        <tr>
            <th colspan="4" style="font-weight: bold; font-size: 16px;">QV FASHION - HÓA ĐƠN BÁN HÀNG</th>
        </tr>
        <tr>
            <th colspan="4">Mã đơn hàng: {{ $order->order_code }}</th>
        </tr>
        <tr>
            <th colspan="4">Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }}</th>
        </tr>
        <tr></tr> <tr>
            <th colspan="4" style="font-weight: bold; background-color: #f2f2f2;">THÔNG TIN KHÁCH HÀNG</th>
        </tr>
        <tr>
            <td colspan="2">Người nhận: {{ $order->shipping_recipient_name }}</td>
            <td colspan="2">Số điện thoại: {{ $order->shipping_phone_number }}</td>
        </tr>
        <tr>
            <td colspan="4">Địa chỉ: {{ $order->shipping_address_detail }}, {{ $order->shipping_ward }}, {{ $order->shipping_district }}, {{ $order->shipping_province }}</td>
        </tr>
        <tr></tr>

        <tr>
            <th style="font-weight: bold; border: 1px solid #000; background-color: #d9d9d9;">Sản phẩm</th>
            <th style="font-weight: bold; border: 1px solid #000; background-color: #d9d9d9; text-align: center;">Số lượng</th>
            <th style="font-weight: bold; border: 1px solid #000; background-color: #d9d9d9; text-align: right;">Đơn giá</th>
            <th style="font-weight: bold; border: 1px solid #000; background-color: #d9d9d9; text-align: right;">Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->details as $item)
            <tr>
                <td style="border: 1px solid #ddd;">{{ $item->product_name }} ({{ $item->variant_info }})</td>
                <td style="border: 1px solid #ddd; text-align: center;">{{ $item->quantity }}</td>
                <td style="border: 1px solid #ddd; text-align: right;">{{ number_format($item->unit_price) }}</td>
                <td style="border: 1px solid #ddd; text-align: right;">{{ number_format($item->sub_total) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align: right; font-weight: bold;">Tạm tính:</td>
            <td style="text-align: right;">{{ number_format($order->total_cost) }}</td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: right; font-weight: bold;">Phí vận chuyển:</td>
            <td style="text-align: right;">{{ number_format($order->shipping_fee) }}</td>
        </tr>
        @if($order->discount_amount > 0)
        <tr>
            <td colspan="3" style="text-align: right; font-weight: bold;">Giảm giá Voucher:</td>
            <td style="text-align: right;">-{{ number_format($order->discount_amount) }}</td>
        </tr>
        @endif
        <tr>
            <td colspan="3" style="text-align: right; font-weight: bold; color: #ff0000;">TỔNG THANH TOÁN:</td>
            <td style="text-align: right; font-weight: bold; color: #ff0000;">{{ number_format($order->final_amount) }}</td>
        </tr>
    </tfoot>
</table>