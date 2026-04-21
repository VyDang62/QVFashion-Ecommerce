<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.4; color: #333; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
        .company-name { font-size: 22px; font-weight: bold; color: #1e40af; }
        .report-title { font-size: 15px; margin-top: 5px; text-transform: uppercase; font-weight: bold; color: #444; }
        .date-range { font-style: italic; color: #666; margin-top: 5px; }
        
        .summary-box { margin-bottom: 20px; }
        .summary-table { width: 100%; border: 1px solid #e2e8f0; border-collapse: collapse; background: #f8fafc; }
        .summary-table td { padding: 10px; border: 1px solid #e2e8f0; width: 50%; }
        .label { color: #64748b; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        .value { font-size: 13px; font-weight: bold; color: #1e293b; }
        .value-profit { color: #059669; } /* Màu xanh cho lợi nhuận */

        .main-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .main-table th { background-color: #1e40af; color: white; border: 1px solid #1e3a8a; padding: 8px; text-align: center; font-size: 10px; }
        .main-table td { border: 1px solid #e2e8f0; padding: 7px 5px; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f1f5f9; }
        
        .footer { margin-top: 40px; width: 100%; }
        .footer-table { width: 100%; }
        .signature-space { height: 60px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">QVFASHION</div>
        <div class="report-title">BÁO CÁO TÀI CHÍNH CHI TIẾT</div>
        <div class="date-range">Thời gian: {{ $startDate }} - {{ $endDate }}</div>
    </div>

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td>
                    <div class="label">Doanh thu (Hàng hóa)</div>
                    <div class="value">{{ number_format($summary['total_revenue']) }} VNĐ</div>
                </td>
                <td>
                    <div class="label">Tổng phí ship thu hộ</div>
                    <div class="value">{{ number_format($summary['total_shipping_fee']) }} VNĐ</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Giá vốn hàng bán (COGS)</div>
                    <div class="value">{{ number_format($summary['total_cogs']) }} VNĐ</div>
                </td>
                <td>
                    <div class="label">Thuế dự tính ({{ $taxRate * 100 }}%)</div>
                    <div class="value">{{ number_format($summary['estimated_tax']) }} VNĐ</div>
                </td>
            </tr>
            <tr class="bg-gray">
                <td>
                    <div class="label">Lợi nhuận ròng</div>
                    <div class="value value-profit">{{ number_format($summary['net_profit']) }} VNĐ</div>
                </td>
                <td>
                    <div class="label">Thực thu (Gồm cả ship)</div>
                    <div class="value">{{ number_format($summary['collected_cash']) }} VNĐ</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th width="15%">Mã Đơn</th>
                <th width="12%">Ngày Đặt</th>
                <th class="text-right">Hàng Hóa</th>
                <th class="text-right">Phí Ship</th>
                <th class="text-right">Giá Vốn</th>
                <th class="text-right">Lợi Nhuận</th>
            </tr>
        </thead>
        <tbody>
            @foreach($financialData as $order)
                @php
                    $ship = (float)($order->shipping_fee ?? 0);
                    $goodsRevenue = $order->final_amount - $ship;
                    $profit = $goodsRevenue - $order->total_cogs;
                @endphp
                <tr>
                    <td class="text-center font-bold" style="font-size: 9px;">{{ strtoupper(substr($order->order_code, 0, 8)) }}...</td>
                    <td class="text-center">{{ $order->created_at->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($goodsRevenue) }}</td>
                    <td class="text-right" style="color: #666;">{{ number_format($ship) }}</td>
                    <td class="text-right">{{ number_format($order->total_cogs) }}</td>
                    <td class="text-right font-bold" style="color: {{ $profit >= 0 ? '#059669' : '#dc2626' }}">
                        {{ number_format($profit) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-gray">
                <td colspan="2" class="text-center font-bold">TỔNG CỘNG</td>
                <td class="text-right font-bold">{{ number_format($summary['total_revenue']) }}</td>
                <td class="text-right font-bold">{{ number_format($summary['total_shipping_fee']) }}</td>
                <td class="text-right font-bold">{{ number_format($summary['total_cogs']) }}</td>
                <td class="text-right font-bold" style="background: #e2e8f0;">{{ number_format($summary['total_revenue'] - $summary['total_cogs']) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td width="60%"></td>
                <td class="text-center">
                    <div>..., ngày {{ now()->format('d') }} tháng {{ now()->format('m') }} năm {{ now()->format('Y') }}</div>
                    <div class="font-bold" style="margin-top: 5px;">NGƯỜI LẬP BIỂU</div>
                    <div class="signature-space"></div>
                    <div class="font-bold">{{ auth()->user()->full_name ?? 'Hệ thống QVFashion' }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>