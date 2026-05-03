<?php

namespace App\Http\Controllers;

use App\Exports\LowStockExport;
use App\Helpers\Logger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class InventoryStatisticsController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('can:dashboard.view_inventory_statistics', only: ['index']),

            new Middleware('can:dashboard.export_inventory', only: ['exportExcel', 'exportPdf']),
        ];
    }
    public function index(Request $request)
    {
        //Giá trị tồn kho theo Danh mục (Dùng biểu đồ Treemap)
        $inventoryValue = DB::table('batches')
            ->join('product_variants', 'batches.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('categories as sub_cat', 'products.category_id', '=', 'sub_cat.id')
            ->leftJoin('categories as parent_cat', 'sub_cat.parent_id', '=', 'parent_cat.id')
            ->select(
                DB::raw("COALESCE(parent_cat.category_name, sub_cat.category_name) as category"),
                DB::raw("SUM(batches.remaining_quantity * batches.purchase_price) as total_value")
            )
            ->groupBy('category')
            ->get();

        //Cảnh báo sắp hết hàng
        $lowStockItems = DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->whereRaw('product_variants.stock_quantity <= product_variants.low_stock_threshold')
            ->where('product_variants.deleted_at', null)
            ->select(
                'products.product_name',
                'product_variants.sku',
                'product_variants.stock_quantity',
                'product_variants.low_stock_threshold'
            )
            ->orderBy('product_variants.stock_quantity', 'asc')
            ->limit(10)
            ->get();

        //Phân tích Biên lợi nhuận theo Lô hàng
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');

        $batchQuery = DB::table('batches')
            ->join('product_variants', 'batches.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select(
                'products.product_name',
                'product_variants.sku',
                'batches.batch_code',
                'batches.purchase_price',
                'product_variants.price as selling_price',
                'batches.created_at',
                DB::raw('(product_variants.price - batches.purchase_price) as potential_profit'),
                DB::raw('ROUND(((product_variants.price - batches.purchase_price) / NULLIF(product_variants.price, 0) * 100), 2) as margin')
            )
            ->when($search, function ($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('products.product_name', 'ilike', "%{$search}%")
                    ->orWhere('batches.batch_code', 'ilike', "%{$search}%")
                    ->orWhere('product_variants.sku', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('batches.created_at', 'desc');

        $batchAnalysis = $batchQuery->paginate($perPage)->withQueryString();

        //Tính Vòng quay hàng tồn kho (Turnover Rate - Ước tính 30 ngày)
        $cogs30Days = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('batches', 'order_details.product_variant_id', '=', 'batches.product_variant_id')
            ->where('orders.order_status', 6)
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->sum(DB::raw('order_details.quantity * batches.purchase_price'));

        $totalInventoryValue = DB::table('batches')->sum(DB::raw('remaining_quantity * purchase_price::bigint'));
        $turnoverRate = $totalInventoryValue > 0 ? round($cogs30Days / $totalInventoryValue, 2) : 0;

        return Inertia::render('admin/Dashboard/InventoryStatistics', [
            'inventoryValue' => $inventoryValue,
            'lowStockItems' => $lowStockItems,
            'batchAnalysis' => $batchAnalysis,
            'metrics' => [
                'total_inventory_value' => $totalInventoryValue,
                'turnover_rate' => $turnoverRate,
                'out_of_stock_count' => DB::table('product_variants')->where('stock_quantity', 0)->count()
            ],
            'filters' => [
                'search' => $search,
                'perPage' => (int) $perPage
            ]
        ]);
    }

    public function exportExcel()
    {
        Logger::log(
            'Export Low Stock Report',
            null, 
            "Đã xuất báo cáo danh sách sản phẩm sắp hết hàng (Excel)",
            [
                'format' => 'Excel',
                'exported_at' => now()->toDateTimeString(),
            ]
        );
        return Excel::download(new LowStockExport(), 'Bao-cao-hang-ton-thap-' . now()->format('dmY-His') . '.xlsx');
    }

    public function exportPdf()
    {
        $items = DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->whereRaw('product_variants.stock_quantity <= product_variants.low_stock_threshold')
            ->whereNull('product_variants.deleted_at')
            ->select('products.product_name', 'product_variants.sku', 'product_variants.stock_quantity', 'product_variants.low_stock_threshold')
            ->get();

        Logger::log(
            'Export Low Stock Report',
            null,
            "Đã xuất báo cáo danh sách sản phẩm sắp hết hàng (PDF). Tổng cộng: " . $items->count() . " sản phẩm.",
            [
                'format' => 'PDF',
                'item_count' => $items->count(),
                'exported_at' => now()->toDateTimeString(),
            ]
        );
        $pdf = Pdf::loadView('admin.reports.low_stock_pdf', compact('items'));
        return $pdf->download('Bao-cao-hang-ton-thap-' . now()->format('dmY-His') . '.pdf');
    }
}
