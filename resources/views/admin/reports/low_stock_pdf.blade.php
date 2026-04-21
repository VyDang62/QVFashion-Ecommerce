<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Báo cáo hàng tồn kho thấp</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 30px; }
        .text-red { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h1>DANH SÁCH SẢN PHẨM SẮP HẾT HÀNG</h1>
        <p>Ngày xuất báo cáo: {{ date('d/m/Y H:i') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Sản phẩm</th>
                <th>SKU</th>
                <th>Tồn kho</th>
                <th>Ngưỡng</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->sku }}</td>
                <td class="{{ $item->stock_quantity == 0 ? 'text-red' : '' }}">
                    {{ $item->stock_quantity }}
                </td>
                <td>{{ $item->low_stock_threshold }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>