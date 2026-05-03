<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class FinancialReportExport implements FromCollection, ShouldAutoSize, WithStyles
{
    protected $report;
    protected $startDate;
    protected $endDate;

    public function __construct($report, $startDate, $endDate)
    {
        $this->report = $report;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $summary = $this->report['summary'];
        $orders = $this->report['financialData'];

        $data = new Collection();

        //Tiêu đề
        $data->push(['BÁO CÁO TÀI CHÍNH CHI TIẾT QVFASHION']);
        $data->push(['Thời gian:', $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')]);
        $data->push(['Ngày xuất bản:', now()->format('d/m/Y H:i')]);
        $data->push(['Người lập biểu:', auth()->user()->full_name ?? 'Hệ thống']);
        $data->push(['']); 

        //Tóm tắt
        $data->push(['TÓM TẮT CHỈ SỐ', 'GIÁ TRỊ (VNĐ)']);
        $data->push(['Doanh thu thuần (Hàng hóa)', $summary['total_revenue']]);
        $data->push(['Giá vốn hàng bán (COGS)', $summary['total_cogs']]);
        $data->push(['Phí ship thu hộ', $summary['total_shipping_fee']]);
        $data->push(['Thuế dự tính (10%)', $summary['estimated_tax']]);
        $data->push(['Lợi nhuận ròng', $summary['net_profit']]);
        $data->push(['Thực thu (Gồm cả ship)', $summary['collected_cash']]);
        $data->push(['Tiền đang treo (Pending)', $summary['pending_cash']]);
        $data->push(['']); 

        //Header bảng chi tiết
        $data->push(['CHI TIẾT ĐƠN HÀNG']);
        $data->push([
            'Mã Đơn',
            'Ngày Đặt',
            'Hàng Hóa (Doanh thu)',
            'Phí Ship Thu Hộ',
            'Giá Vốn',
            'Giảm Giá',
            'Lợi Nhuận',
            'Trạng Thái'
        ]);

        //Dữ liệu đơn hàng
        foreach ($orders as $order) {
            $ship = (float)data_get($order, 'shipping_fee', 0);
            $finalAmount = (float)data_get($order, 'final_amount', 0);
            $cogs = (float)data_get($order, 'total_cogs', 0);
            $discount = (float)data_get($order, 'discount_amount', 0);
            
            $goodsRevenue = $finalAmount - $ship;
            $profit = $goodsRevenue - $cogs;

            $data->push([
                data_get($order, 'order_code'),
                data_get($order, 'created_at') instanceof \Carbon\Carbon 
                    ? data_get($order, 'created_at')->format('d/m/Y H:i')
                    : \Carbon\Carbon::parse(data_get($order, 'created_at'))->format('d/m/Y H:i'),
                $goodsRevenue,
                $ship,
                $cogs,
                $discount,
                $profit,
                data_get($order, 'status_info.label', 'N/A')
            ]);
        }

        //Dòng tổng cộng
        $data->push([
            'TỔNG CỘNG',
            '',
            $summary['total_revenue'],
            $summary['total_shipping_fee'],
            $summary['total_cogs'],
            $summary['total_discount'],
            $summary['total_revenue'] - $summary['total_cogs'],
            ''
        ]);

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E40AF']]],
            6 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'CBD5E1']]],
            11 => ['font' => ['bold' => true, 'color' => ['rgb' => '059669']]],
            16 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1E40AF']],
            ],
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F1F5F9']]
            ],
        ];
    }
}