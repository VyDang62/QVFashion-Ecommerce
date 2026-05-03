<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SingleOrderExport;
use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\FlashSaleItem;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\InventoryService;
use App\Services\VnpayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class OrderController extends Controller implements HasMiddleware
{
    protected $inventoryService;
    protected $vnpayService;

    public function __construct(InventoryService $inventoryService, VnpayService $vnpayService)
    {
        $this->inventoryService = $inventoryService;
        $this->vnpayService = $vnpayService;
    }
    public static function middleware(): array
    {
        return [
            new Middleware('can:orders.view', only: ['index', 'show']),

            new Middleware('can:orders.edit', only: ['edit']),

            new Middleware('permission:orders.edit|orders.approve|orders.cancel', only: ['update']),

            new Middleware('can:orders.export', only: ['exportExcel', 'exportPdf']),
        ];
    }
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $status = $request->input('status');
        $search = $request->input('search');

        $query = Order::with(['user']);

        if($request->filled('search')){
            $query->where(function ($q) use ($search){
                $q->where('id','like','%'.$search.'%')
                  ->orWhere('order_code','like','%'.$search.'%')
                  ->orWhereHas('user',function ($sub) use($search){
                    $sub->where('full_name', 'ilike', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('order_status', $status);
        }

        $orders = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'perPage' => (int) $perPage,
                'status' => $status
            ]
        ]);
    }

    public function edit($id)
    {
        $order = Order::with(['user', 'details.variant.product', 'voucher'])->findOrFail($id);

        return Inertia::render('admin/Orders/Edit',[
            'order' => $order 
        ]);
    }

    public function show($id)
    {
        $order = Order::with(['user', 'details.variant.product', 'voucher'])->findOrFail($id);

        return Inertia::render('admin/Orders/Show',[
            'order' => $order 
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $request->validate([
            'order_status' => 'required|integer|in:0,4,5,6,7,8,9,10', 
        ]);

        $newStatus = (int) $request->order_status;
        $oldStatus = (int) $order->order_status;
        $oldStatusLabel = $order->status_info['label'];
        //Hủy đơn (0): Chỉ cho phép từ 1 (COD) hoặc 3 (Đã thanh toán)
        if ($newStatus === 0 && !in_array($oldStatus, [1, 3])) {
            return back()->with('error', 'Đơn hàng đã vận chuyển hoặc hoàn thành, không thể hủy!');
        }

        //Duyệt giao hàng (4): Chỉ cho phép từ 1 hoặc 3
        if ($newStatus === 4 && !in_array($oldStatus, [1, 3])) {
            return back()->with('error', 'Chỉ có thể duyệt đơn từ trạng thái Chờ xử lý hoặc Đã thanh toán!');
        }

        //Xác nhận đã giao (5): Chỉ cho phép khi đang giao (4)
        if ($newStatus === 5 && $oldStatus !== 4) {
            return back()->with('error', 'Đơn hàng phải ở trạng thái "Đang giao hàng" mới có thể xác nhận Đã giao!');
        }

        //Hoàn thành (6): Chỉ cho phép khi đã giao (5)
        if ($newStatus === 6 && $oldStatus !== 5) {
            return back()->with('error', 'Đơn hàng phải ở trạng thái "Đã giao hàng" mới có thể bấm Hoàn thành!');
        }
        //Hoàn hàng và hoàn tiền
        if ($newStatus === 8 && $oldStatus !== 7) return back()->with('error', 'Chỉ duyệt thu hồi khi có yêu cầu trả hàng!');
        if ($newStatus === 9 && $oldStatus !== 8) return back()->with('error', 'Hàng chưa được thu hồi, không thể xác nhận nhận hàng!');
        if ($newStatus === 10 && $oldStatus !== 9) return back()->with('error', 'Phải nhận được hàng trả về kho mới có thể hoàn tiền!');
        try {
            DB::transaction(function () use ($newStatus, $oldStatus, $oldStatusLabel, $order) {
                if ($newStatus === 0 || $newStatus === 9) {
                    //Hoàn kho
                    $this->inventoryService->restoreStock($order); 
                    
                    //Hoàn Voucher
                    if ($order->voucher_id) {
                        Voucher::where('id', $order->voucher_id)->decrement('used_count');
                        VoucherUsage::where('order_id', $order->id)->delete();
                    }

                    //Hoàn Flash Sale
                    foreach($order->details as $detail){
                        if($detail->flash_sale_id){
                            FlashSaleItem::where('flash_sale_id', $detail->flash_sale_id)
                                ->where('product_variant_id', $detail->product_variant_id)
                                ->decrement('sold_quantity', $detail->quantity);
                        }
                    }
                    Logger::log(
                        'Cancel Order',
                        $order,
                        "Đã hủy đơn hàng: {$order->order_code}.",
                        ['old_status' => $oldStatus, 'new_status' => 0]
                    );
                }
                //Gọi api hoàn tiền
                if ($newStatus === 10 && $order->payment_method === 'banking') {
                    $refundResult = $this->vnpayService->refund($order);

                    // Nếu VNPAY trả về mã lỗi (khác 00), chúng ta quăng lỗi để Rollback transaction
                    if (($refundResult['vnp_ResponseCode'] ?? '') !== '00') {
                        throw new \Exception("Lỗi VNPAY Refund: " . ($refundResult['vnp_Message'] ?? 'Không xác định'));
                    }
                }

                $order->update(['order_status' => $newStatus]);
                $newStatusLabel = $order->status_info['label'];

                Logger::log(
                    $newStatus === 0 ? 'Cancel Order' : 'Update Order Status',
                    $order,
                    "Đã chuyển trạng thái đơn {$order->order_code} từ [{$oldStatusLabel}] sang [{$newStatusLabel}]",
                    ['old_status' => $oldStatus, 'new_status' => $newStatus]
                );
            });

            return back()->with('success', 'Cập nhật trạng thái đơn hàng thành công!');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Lỗi xử lý: ' . $e->getMessage()]);
        }
    }

    public function exportExcel($id)
    {
        $order = Order::with(['user', 'details.variant', 'voucher'])->findOrFail($id);
        Logger::log('Export Order', $order, "Đã xuất file Excel đơn hàng: {$order->order_code}");
        return Excel::download(new SingleOrderExport($order), "Don-hang-{$order->order_code}.xlsx");
    }

    public function exportPdf($id)
    {
        $order = Order::with(['user', 'details.variant', 'voucher'])->findOrFail($id);
        
        $pdf = Pdf::loadView('admin.pdf.order_invoice', compact('order'))
                ->setPaper('a4', 'portrait');
        Logger::log('Export Order', $order, "Đã xuất hóa đơn PDF đơn hàng: {$order->order_code}");
        return $pdf->download("Hoa-don-{$order->order_code}.pdf");
    }
}
