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
            new Middleware('can:dashboard.export_financial', only: ['export', 'exportPdf']),
        ];
    }
    /**
     * Tạo Query cơ bản để lấy dữ liệu đơn hàng và tính toán giá vốn (COGS)
     */
    private function getBaseFinancialQuery($startDate, $endDate, $search = null)
    {
        //Tạo subquery tính giá nhập trung bình của từng sản phẩm từ các lô hàng
        //Tính giá vốn (COGS)
        $batchCostSubquery = DB::table('batches')
            ->select('product_variant_id', DB::raw('AVG(purchase_price) as avg_cost'))
            ->groupBy('product_variant_id');

        //Query đơn hàng 
        $query = Order::query()
            ->leftJoin('order_details', 'orders.id', '=', 'order_details.order_id')
            ->leftJoinSub($batchCostSubquery, 'costs', function ($join) {
                $join->on('order_details.product_variant_id', '=', 'costs.product_variant_id');
            })
            //Lọc theo thời gian và bỏ đơn bị hủy
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.order_status', '!=', 0)
            //Tính tổng vốn từng đơn: SUM(số lượng * giá vốn trung bình)
            ->select(
                'orders.*',
                DB::raw('SUM(order_details.quantity * COALESCE(costs.avg_cost, 0)) as total_cogs')
            )
            ->groupBy('orders.id')
            ->orderBy('orders.created_at', 'desc');

        if ($search) {
            $query->where('orders.order_code', 'like', "%{$search}%");
        }

        return $query;
    }
    /**
     * Tính toán số liệu tổng hợp và chuẩn bị dữ liệu danh sách
     */
    private function calculateFinancialData($startDate, $endDate, $search = null, $perPage = null)
    {
        $taxRate = Setting::getTaxRate();
        $query = $this->getBaseFinancialQuery($startDate, $endDate, $search);

        $allResultsForSummary = (clone $query)->get();
        //Duyệt qua tất cả đơn hàng để gom nhóm số liệu
        //Accumulator (Tích lũy)
        $summary = $allResultsForSummary->reduce(function ($acc, $order) {
            $shippingFee = (float) ($order->shipping_fee ?? 0);
            $totalPaidByCustomer = (float) $order->final_amount;
            // Chỉ tính Doanh thu & Lợi nhuận cho các đơn hàng đã HOÀN THÀNH
            if ($order->order_status == 6) {
                //Doanh thu thuần = số tiền khách trả - phí vận chuyển
                $netGoodsRevenue = $totalPaidByCustomer - $shippingFee;
                $acc['total_revenue'] += $netGoodsRevenue;
                $acc['total_cogs'] += (float) $order->total_cogs;
                $acc['total_discount'] += (float) $order->discount_amount;
                $acc['collected_cash'] += $totalPaidByCustomer;
                $acc['total_shipping_fee'] += $shippingFee;
            } elseif (!in_array($order->order_status, [0, 10])) {
                $acc['pending_cash'] += $totalPaidByCustomer;
            }
            return $acc;
        }, [
            'total_revenue' => 0, 'total_cogs' => 0, 'total_discount' => 0,
            'collected_cash' => 0, 'pending_cash' => 0, 'total_shipping_fee' => 0,
        ]);

        //Tính Thuế dự tính dựa trên doanh thu
        $summary['estimated_tax'] = $summary['total_revenue'] * $taxRate;
        //Lợi nhuận ròng = Doanh thu - Giá vốn - Thuế
        $summary['net_profit'] = $summary['total_revenue'] - $summary['total_cogs'] - $summary['estimated_tax'];

        $financialData = $perPage ? $query->paginate($perPage)->withQueryString() : $query->get();

        return [
            'financialData' => $financialData,
            'summary' => $summary,
            'taxRate' => $taxRate
        ];
    }

    public function index(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfMonth();
        
        $search = $request->input('search');
        $perPage = $request->input('perPage', 10);

        $report = $this->calculateFinancialData($startDate, $endDate, $search, $perPage);

        return Inertia::render('admin/Dashboard/FinancialReport', array_merge($report, [
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'search' => $search,
                'perPage' => (int) $perPage,
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