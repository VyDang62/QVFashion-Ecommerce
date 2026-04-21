<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Exports\FinancialReportExport;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('can:dashboard.view_financial'),
            new Middleware('can:dashboard.export_finalcial', only: ['export', 'exportPdf']),
        ];
    }

    /**
     * Logic trung tâm: Tính toán toàn bộ con số tài chính cho QVFashion
     * ĐÃ ĐIỀU CHỈNH: Phí ship do khách trả (Thu hộ - Chi hộ)
     */

    private function calculateFinancialData($startDate, $endDate)
    {
        $taxRate = Setting::getTaxRate(); // Thuế VAT 10%

        // 1. Tính giá vốn trung bình (COGS) từ các lô hàng
        $batchCostSubquery = DB::table('batches')
            ->select('product_variant_id', DB::raw('AVG(purchase_price) as avg_cost'))
            ->groupBy('product_variant_id');

        // 2. Lấy danh sách đơn hàng (Trừ đơn hủy)
        $orders = Order::query()
            ->leftJoin('order_details', 'orders.id', '=', 'order_details.order_id')
            ->leftJoinSub($batchCostSubquery, 'costs', function ($join) {
                $join->on('order_details.product_variant_id', '=', 'costs.product_variant_id');
            })
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.order_status', '!=', 0)
            ->select(
                'orders.*',
                DB::raw('SUM(order_details.quantity * COALESCE(costs.avg_cost, 0)) as total_cogs')
            )
            ->groupBy('orders.id')
            ->orderBy('orders.created_at', 'desc')
            ->get();

        // 3. Tính toán tổng hợp
        $summary = $orders->reduce(function ($acc, $order) {
            $shippingFee = (float) ($order->shipping_fee ?? 0);
            $totalPaidByCustomer = (float) $order->final_amount;

            // Chỉ tính Doanh thu/Lợi nhuận cho đơn đã HOÀN THÀNH (Status 6)
            if ($order->order_status == 6) {
                // DOANH THU THUẦN = Tổng khách trả - Phí ship khách trả
                $netGoodsRevenue = $totalPaidByCustomer - $shippingFee;

                $acc['total_revenue'] += $netGoodsRevenue; // Doanh thu hàng hóa sạch
                $acc['total_cogs'] += (float) $order->total_cogs;
                $acc['total_discount'] += (float) $order->discount_amount;
                $acc['total_shipping_fee'] += $shippingFee; // Theo dõi phí thu hộ
                $acc['collected_cash'] += $totalPaidByCustomer; // Tiền thực thu vào ví (có cả ship)
            } 
            elseif (!in_array($order->order_status, [0, 10])) {
                $acc['pending_cash'] += $totalPaidByCustomer;
            }
            return $acc;
        }, [
            'total_revenue' => 0,
            'total_cogs' => 0,
            'total_discount' => 0,
            'total_shipping_fee' => 0,
            'collected_cash' => 0,
            'pending_cash' => 0,
        ]);

        // 4. Tính toán chỉ số cuối cùng dựa trên doanh thu hàng hóa
        // Thuế dự tính 10% của Doanh thu hàng hóa (Không đánh thuế lên tiền ship thu hộ)
        $summary['estimated_tax'] = $summary['total_revenue'] * $taxRate;
        
        // Lợi nhuận ròng = Doanh thu hàng - Giá vốn - Thuế
        $summary['net_profit'] = $summary['total_revenue'] - $summary['total_cogs'] - $summary['estimated_tax'];

        return [
            'financialData' => $orders,
            'summary' => $summary,
            'taxRate' => $taxRate
        ];
    }

    // Các hàm index, export, exportPdf sử dụng hàm calculateFinancialData trên...
    
    public function index(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfMonth();

        $report = $this->calculateFinancialData($startDate, $endDate);

        return Inertia::render('admin/Dashboard/FinancialReport', array_merge($report, [
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ]));
    }

    private function getReportDates(Request $request)
    {
        return [
            'start' => $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfMonth(),
            'end' => $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfMonth(),
        ];
    }
    private function getFileName($extension, $start)
    {
        return 'Bao-cao-tai-chinh-QVFashion-' . $start->format('dmY') . '-' . now()->format('His') . '.' . $extension;
    }
    public function export(Request $request)
    {
        $dates = $this->getReportDates($request);
        $report = $this->calculateFinancialData($dates['start'], $dates['end']);

        Logger::log(
            'Export Financial Report',
            null, 
            "Đã xuất báo cáo tài chính (Excel) từ {$dates['start']->format('d/m/Y')} đến {$dates['end']->format('d/m/Y')}",
            [
                'format' => 'Excel',
                'filters' => [
                    'start_date' => $dates['start']->toDateString(),
                    'end_date' => $dates['end']->toDateString(),
                ],
                'total_revenue' => $report['summary']['total_revenue']
            ]
        );

        $fileName = $this->getFileName('xlsx', $dates['start']);
        return Excel::download(new FinancialReportExport($report, $dates['start'], $dates['end']), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $dates = $this->getReportDates($request);
        $report = $this->calculateFinancialData($dates['start'], $dates['end']);

        Logger::log(
            'Export Financial Report',
            null,
            "Đã xuất báo cáo tài chính (PDF) từ {$dates['start']->format('d/m/Y')} đến {$dates['end']->format('d/m/Y')}",
            [
                'format' => 'PDF',
                'filters' => [
                    'start_date' => $dates['start']->toDateString(),
                    'end_date' => $dates['end']->toDateString(),
                ],
                'net_profit' => $report['summary']['net_profit']
            ]
        );

        $fileName = $this->getFileName('pdf', $dates['start']);

        $pdf = Pdf::loadView('admin.reports.financial_pdf', array_merge($report, [
            'startDate' => $dates['start']->format('d/m/Y'),
            'endDate' => $dates['end']->format('d/m/Y'),
        ]));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }
}