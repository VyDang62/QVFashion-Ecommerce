<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Carbon\Carbon;

class ProductStatisticsController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('can:dashboard.view_product_statistics'),
        ];
    }
    public function index(Request $request)
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date'))->startOfDay() 
            : now()->startOfMonth();
            
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date'))->endOfDay() 
            : now()->endOfMonth();

        // 1. Subquery tính giá vốn trung bình (COGS) - Đồng bộ với FinancialReport
        $batchCostSubquery = DB::table('batches')
            ->select('product_variant_id', DB::raw('AVG(purchase_price) as avg_purchase_price'))
            ->groupBy('product_variant_id');

        /**
         * 2. Logic tính Doanh thu thực tế của từng sản phẩm (Item Net Revenue)
         * Công thức: Giá trị hàng - (Tỷ lệ đóng góp vào đơn hàng * Tổng giảm giá Voucher)
         * Lưu ý: Không tính Shipping Fee vào đây vì khách trả ship (thu hộ chi hộ).
         */
        $actualRevenueSql = "order_details.sub_total - 
            (CASE WHEN orders.total_cost > 0 
                  THEN (order_details.sub_total::float / orders.total_cost * orders.discount_amount) 
                  ELSE 0 END)";

        // 3. Truy vấn cơ sở cho các thống kê bán hàng (Chỉ tính đơn Hoàn thành)
        $baseQuery = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_details.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->leftJoinSub($batchCostSubquery, 'costs', function ($join) {
                $join->on('order_details.product_variant_id', '=', 'costs.product_variant_id');
            })
            ->where('orders.order_status', 6)
            ->whereBetween('orders.created_at', [$startDate, $endDate]);

        // --- TOP 10 SẢN PHẨM BÁN CHẠY (Số lượng) ---
        $topSelling = (clone $baseQuery)
            ->select(
                'products.id',
                'products.product_name as name', 
                DB::raw('SUM(order_details.quantity) as total_qty'),
                DB::raw("SUM($actualRevenueSql) as total_revenue")
            )
            ->groupBy('products.id', 'products.product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // --- THỐNG KÊ DANH MỤC PHÂN CẤP ---
        $rawCategoryData = (clone $baseQuery)
            ->join('categories as sub_cat', 'products.category_id', '=', 'sub_cat.id')
            ->leftJoin('categories as parent_cat', 'sub_cat.parent_id', '=', 'parent_cat.id')
            ->select(
                'sub_cat.id as cat_id',
                'sub_cat.category_name as cat_name',
                'sub_cat.parent_id',
                'parent_cat.category_name as parent_name',
                DB::raw("SUM($actualRevenueSql) as revenue"),
                DB::raw("SUM(($actualRevenueSql) - (COALESCE(costs.avg_purchase_price, 0) * order_details.quantity)) as profit")
            )
            ->groupBy('sub_cat.id', 'sub_cat.category_name', 'sub_cat.parent_id', 'parent_cat.category_name')
            ->get();

        // Tổ chức dữ liệu Hierarchy (PHP)
        $hierarchicalStats = [];
        foreach ($rawCategoryData as $item) {
            $revenue = (float) $item->revenue;
            $profit = (float) $item->profit;

            $pId = $item->parent_id ?? $item->cat_id;
            if (!isset($hierarchicalStats[$pId])) {
                $hierarchicalStats[$pId] = [
                    'name' => $item->parent_name ?? $item->cat_name,
                    'revenue' => 0, 'profit' => 0, 'children' => []
                ];
            }
            
            if ($item->parent_id) {
                $hierarchicalStats[$pId]['revenue'] += $revenue;
                $hierarchicalStats[$pId]['profit'] += $profit;
                $hierarchicalStats[$pId]['children'][] = [
                    'name' => $item->cat_name,
                    'revenue' => $revenue,
                    'profit' => $profit
                ];
            } else {
                // Là danh mục cha nhưng có phát sinh doanh thu trực tiếp
                $hierarchicalStats[$pId]['revenue'] += $revenue;
                $hierarchicalStats[$pId]['profit'] += $profit;
            }
        }

        // --- SẢN PHẨM HIỆU SUẤT THẤP (Ít người mua nhất) ---
        $lowPerformance = DB::table('products')
            ->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('order_details', function($join) use ($startDate, $endDate) {
                $join->on('product_variants.id', '=', 'order_details.product_variant_id')
                     ->whereIn('order_details.order_id', function($query) use ($startDate, $endDate) {
                         $query->select('id')->from('orders')
                               ->where('order_status', 6)
                               ->whereBetween('created_at', [$startDate, $endDate]);
                     });
            })
            ->select(
                'products.id',
                'products.product_name as name',
                DB::raw('COALESCE(SUM(order_details.quantity), 0) as sold_qty')
            )
            ->groupBy('products.id', 'products.product_name')
            ->orderBy('sold_qty', 'asc')
            ->limit(5)
            ->get();

        // --- TOP 10 SẢN PHẨM DOANH THU CAO NHẤT ---
        $topRevenue = (clone $baseQuery)
            ->select(
                'products.id',
                'products.product_name as name', 
                DB::raw("SUM($actualRevenueSql) as revenue")
            )
            ->groupBy('products.id', 'products.product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return Inertia::render('admin/Dashboard/ProductStatistics', [
            'topSelling' => $topSelling,
            'categoryStats' => array_values($hierarchicalStats),
            'lowPerformance' => $lowPerformance,
            'topRevenue' => $topRevenue,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]
        ]);
    }
}