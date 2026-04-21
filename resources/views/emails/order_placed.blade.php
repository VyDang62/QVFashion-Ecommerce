<h2>Cảm ơn bạn đã đặt hàng tại QV Fashion!</h2>
<p>Chào <strong>{{ $order->shipping_recipient_name }}</strong>,</p>
<p>Đơn hàng của bạn đã được tiếp nhận và đang trong quá trình xử lý.</p>

<h3>Thông tin đơn hàng: {{ $order->order_code }}</h3>
<h4>Phương thức thanh toán: {{ $order->payment_method }}</h3>
<h4>Mã giao dịch: {{ $order->id }}</h3>
<table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f4f4f4;">
            <th>Sản phẩm</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->details as $detail)
        <tr>
            <td>{{ $detail->product_name }} ({{ $detail->variant_info }})</td>
            <td align="center">{{ $detail->quantity }}</td>
            <td align="right">{{ number_format($detail->unit_price) }}đ</td>
            <td align="right">{{ number_format($detail->sub_total) }}đ</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" align="right">Tạm tính:</td>
            <td align="right">{{ number_format($order->total_cost) }}đ</td>
        </tr>
        <tr>
            <td colspan="3" align="right">Giảm giá:</td>
            <td align="right">-{{ number_format($order->discount_amount) }}đ</td>
        </tr>
        <tr>
            <td colspan="3" align="right">Phí vận chuyển:</td>
            <td align="right">{{ number_format($order->final_amount - ($order->total_cost - $order->discount_amount)) }}đ</td>
        </tr>
        <tr style="font-weight: bold; font-size: 1.2em;">
            <td colspan="3" align="right">Tổng cộng:</td>
            <td align="right" style="color: #ee4d2d;">{{ number_format($order->final_amount) }}đ</td>
        </tr>
    </tfoot>
</table>

<h3>Địa chỉ nhận hàng:</h3>
<p>
    {{ $order->shipping_recipient_name }}<br>
    {{ $order->shipping_phone_number }}<br>
    {{ $order->shipping_address_detail }}, {{ $order->shipping_ward }}, {{ $order->shipping_district }}, {{ $order->shipping_province }}
</p>

<p>Chúng tôi sẽ sớm liên hệ với bạn để xác nhận ngày giao hàng.</p>