<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller implements HasMiddleware
{   
    public static function middleware()
    {
        return [
            new Middleware('can:dashboard.view_overall_dashboard'),
        ];
    }
    public function index(Request $request)
    {
        // 1. Tham số lọc
        $selectedYear = $request->input('year', Carbon::now()->year);
        $period = $request->input('period', 'monthly');
        $taxRate = Setting::getTaxRate(); // Đồng bộ 10% VAT

        $startOfCurrentMonth = now()->startOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();

        // 2. Logic SQL dùng chung (Đồng bộ với FinancialReport)
        $batchCostSubquery = DB::table('batches')
            ->select('product_variant_id', DB::raw('AVG(purchase_price) as avg_purchase_price'))
            ->groupBy('product_variant_id');

        // Công thức tính Doanh thu hàng hóa sạch (Không tính ship khách trả)
        $actualRevenueSql = "order_details.sub_total - 
            (CASE WHEN orders.total_cost > 0 
                  THEN (order_details.sub_total::float / orders.total_cost * orders.discount_amount) 
                  ELSE 0 END)";
        
        // Công thức Lợi nhuận ròng: (Doanh thu hàng sạch * 0.9) - COGS 
        // (Nhân 0.9 tương đương với việc trừ đi 10% thuế trên doanh thu)
        $netProfitSql = "(($actualRevenueSql) * (1 - $taxRate)) - (COALESCE(costs.avg_purchase_price, 0) * order_details.quantity)";

        $calculateGrowth = function ($current, $last) {
            if ($last <= 0) return $current > 0 ? 100 : 0;
            return round((($current - $last) / $last) * 100, 1);
        };

        // --- SECTION 1: ECOMMERCE METRICS ---

        // A. Khách hàng mới
        $currentUsers = User::role('customer')->where('created_at', '>=', $startOfCurrentMonth)->count();
        $lastMonthUsers = User::role('customer')->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        // B. Tổng đơn hàng (Không tính đơn hủy)
        $currentOrders = Order::where('order_status', '!=', 0)->where('created_at', '>=', $startOfCurrentMonth)->count();
        $lastMonthOrders = Order::where('order_status', '!=', 0)->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        // C. Doanh thu thuần (Chỉ tiền hàng, trừ voucher, Status 6)
        $getRevenue = function ($start, $end = null) {
            $q = Order::where('order_status', 6);
            $end ? $q->whereBetween('created_at', [$start, $end]) : $q->where('created_at', '>=', $start);
            // total_cost trong schema của bạn là tổng tiền hàng trước voucher
            return $q->sum(DB::raw('total_cost - discount_amount'));
        };
        $currentRevenue = $getRevenue($startOfCurrentMonth);
        $lastMonthRevenue = $getRevenue($startOfLastMonth, $endOfLastMonth);

        // D. Lợi nhuận ròng (Đã trừ COGS và 10% thuế)
        $getNetProfit = function ($start, $end = null) use ($batchCostSubquery, $netProfitSql) {
            $query = DB::table('order_details')
                ->join('orders', 'order_details.order_id', '=', 'orders.id')
                ->leftJoinSub($batchCostSubquery, 'costs', function ($join) {
                    $join->on('order_details.product_variant_id', '=', 'costs.product_variant_id');
                })
                ->where('orders.order_status', 6);
            
            $end ? $query->whereBetween('orders.created_at', [$start, $end]) : $query->where('orders.created_at', '>=', $start);

            return $query->select(DB::raw("SUM($netProfitSql) as net_profit"))->value('net_profit') ?? 0;
        };
        $currentProfit = $getNetProfit($startOfCurrentMonth);
        $lastMonthProfit = $getNetProfit($startOfLastMonth, $endOfLastMonth);

        // --- SECTION 2: BIỂU ĐỒ ANALYTICS ---

        $analytics = $this->getAnalyticsData($period, $selectedYear, $batchCostSubquery, $actualRevenueSql, $netProfitSql);
        $monthlySalesData = $this->getAnalyticsData('monthly', $selectedYear, $batchCostSubquery, $actualRevenueSql, $netProfitSql);

        // Thống kê User hàng tháng
        $monthlyUsers = array_fill(0, 12, 0);
        $userData = User::role('customer')
            ->whereYear('created_at', $selectedYear)
            ->select(DB::raw('EXTRACT(MONTH FROM created_at) as month'), DB::raw('COUNT(*) as count'))
            ->groupBy('month')->get();
        foreach ($userData as $item) { $monthlyUsers[(int)$item->month - 1] = (int)$item->count; }

        return Inertia::render('admin/Dashboard/Ecommerce', [
            'stats' => [
                'monthly_users'   => number_format($currentUsers),
                'users_growth'    => $calculateGrowth($currentUsers, $lastMonthUsers),
                'monthly_orders'  => number_format($currentOrders),
                'orders_growth'   => $calculateGrowth($currentOrders, $lastMonthOrders),
                'monthly_revenue' => $currentRevenue,
                'revenue_growth'  => $calculateGrowth($currentRevenue, $lastMonthRevenue),
                'monthly_profit'  => $currentProfit,
                'profit_growth'   => $calculateGrowth($currentProfit, $lastMonthProfit),
            ],
            'filters' => [
                'year' => (int)$selectedYear,
                'period' => $period,
                'years' => range(Carbon::now()->year, 2023)
            ],
            'analyticsData' => [
                'labels' => $analytics['labels'],
                'revenue' => $analytics['revenues'],
                'profit' => $analytics['profits'],
            ],
            'monthlySalesData' => [
                'series' => [['name' => 'Số lượng đơn hàng', 'data' => $monthlySalesData['order_counts']]]
            ],
            'monthlyUsersData' => [
                'series' => [['name' => 'Khách hàng mới', 'data' => $monthlyUsers]]
            ],
            'recentOrders' => $this->getRecentOrders(),
        ]);
    }

    private function getAnalyticsData($period, $year, $batchCostSubquery, $actualRevenueSql, $netProfitSql)
    {
        $query = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->leftJoinSub($batchCostSubquery, 'costs', function ($join) {
                $join->on('order_details.product_variant_id', '=', 'costs.product_variant_id');
            })
            ->where('orders.order_status', 6);

        if ($period === 'annually') {
            $labels = range($year - 4, $year);
            $query->whereIn(DB::raw('EXTRACT(YEAR FROM orders.created_at)'), $labels)
                  ->select(DB::raw('EXTRACT(YEAR FROM orders.created_at) as time_unit'));
            $dataSize = 5;
        } elseif ($period === 'quarterly') {
            $labels = ['Q1', 'Q2', 'Q3', 'Q4'];
            $query->whereYear('orders.created_at', $year)
                  ->select(DB::raw('EXTRACT(QUARTER FROM orders.created_at) as time_unit'));
            $dataSize = 4;
        } else {
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $query->whereYear('orders.created_at', $year)
                  ->select(DB::raw('EXTRACT(MONTH FROM orders.created_at) as time_unit'));
            $dataSize = 12;
        }

        $results = $query->addSelect([
            DB::raw("SUM($actualRevenueSql) as revenue"),
            DB::raw("SUM($netProfitSql) as profit"), // Đã đồng bộ trừ thuế
            DB::raw('COUNT(DISTINCT orders.id) as order_count')
        ])->groupBy('time_unit')->get();

        $revenues = array_fill(0, $dataSize, 0);
        $profits = array_fill(0, $dataSize, 0);
        $orderCounts = array_fill(0, $dataSize, 0);

        foreach ($results as $row) {
            $index = ($period === 'annually') ? array_search((int)$row->time_unit, $labels) : (int)$row->time_unit - 1;
            if ($index !== false) {
                $revenues[$index] = (float)$row->revenue;
                $profits[$index] = (float)$row->profit;
                $orderCounts[$index] = (int)$row->order_count;
            }
        }

        return ['labels' => $labels, 'revenues' => $revenues, 'profits' => $profits, 'order_counts' => $orderCounts];
    }

    private function getRecentOrders() 
    {
        return Order::latest()->take(6)->get()->map(fn($o) => [
            'id' => $o->id,
            'order_code' => $o->order_code,
            'shipping_recipient_name' => $o->shipping_recipient_name,
            'final_amount' => $o->final_amount,
            'status_info' => $o->status_info,
        ]);
    }
}