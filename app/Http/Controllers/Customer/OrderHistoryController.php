<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrderHistoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    public function index()
    {
        $orders = Auth::user()->orders()
        ->with([
            'details.variant.images',
            'details.variant.product.images' => function($q) {
                $q->where('is_primary', true)->whereNull('variant_id');
            }
        ])
        ->latest()       
        ->get();
        return Inertia::render('customer/OrderHistory',[
            'orders' => $orders,
        ]);
    }

    public function show($order_code)
    {
        $order = Order::where('order_code', $order_code)
            ->where('user_id', Auth::id())
            ->with(['details.variant.product'])
            ->firstOrFail();

        return Inertia::render('customer/OrderShow', [
            'order' => $order
        ]);
    }

    public function update(Request $request,$order_code)
    {
        //Tìm đơn bằng order_code và đơn phải thuộc về user đang đăng nhập
        $order = Order::where('order_code', $order_code)
        ->where('user_id', Auth::id())
        ->firstOrFail();

        $request->validate([
            'order_status' => 'required|integer|in:0,6,7',
        ]);

        $newStatus = (int) $request->order_status;
        $oldStatus = (int) $order->order_status;

        //Hủy (0)
        if ($newStatus === 0 && !in_array($oldStatus, [1, 3])) {
            return back()->with('error', 'Đơn hàng đã được giao hoặc không ở trạng thái có thể hủy.');
        }

        //Hoàn thành (6)
        if ($newStatus === 6 && $oldStatus !== 5) {
            return back()->with('error', 'Bạn chỉ có thể xác nhận khi đơn hàng đã được giao đến bạn.');
        }
        //Yêu cầu trả hàng (7)
        if ($newStatus === 7 && $oldStatus !== 5) {
            return back()->with('error', 'Bạn chỉ có thể yêu cầu trả hàng cho những đơn hàng đã giao thành công.');
        }
        try {
            DB::transaction(function () use ($newStatus, $order) {
                
                if ($newStatus === 0) {
                    //Hoàn kho
                    $this->inventoryService->restoreStock($order);

                    //Hoàn Voucher
                    if ($order->voucher_id) {
                        \App\Models\Voucher::where('id', $order->voucher_id)->decrement('used_count');
                        \App\Models\VoucherUsage::where('order_id', $order->id)->delete();
                    }

                    //Hoàn Flash Sale
                    foreach ($order->details as $detail) {
                        if ($detail->flash_sale_id) {
                            DB::table('flash_sale_items')
                                ->where('flash_sale_id', $detail->flash_sale_id)
                                ->where('product_variant_id', $detail->product_variant_id)
                                ->decrement('sold_quantity', $detail->quantity);
                        }
                    }
                }

                // Cập nhật trạng thái
                $order->update(['order_status' => $newStatus]);
            });

            $msg = match($newStatus) {
                0 => 'Hủy đơn hàng thành công!',
                6 => 'Cảm ơn bạn vì đã mua hàng ở QV Fashion!',
                7 => 'Yêu cầu trả hàng của bạn đã được gửi. Vui lòng chờ hệ thống xác nhận!',
                default => 'Cập nhật trạng thái đơn hàng thành công!'
            };
            return back()->with('success', $msg);

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Có lỗi xảy ra trong quá trình xử lý: ' . $e->getMessage()]);
        }
    }
}
