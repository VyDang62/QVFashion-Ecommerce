<?php

namespace App\Console\Commands;

use App\Models\FlashSaleItem;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\InventoryService;
use DB;
use Illuminate\Console\Command;

class CancelExpiredBankingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-expired-banking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hủy đơn hàng Banking quá hạn 30p và hoàn lại kho, voucher, flash sale';

    /**
     * Execute the console command.
     */
    public function handle(InventoryService $inventoryService)
    {
        $expiredOrders = Order::where('order_status',2)
            ->where('payment_method','banking')
            ->where('created_at','<',now()->subMinutes(30))
            ->with('details')
            ->get();
        
        if($expiredOrders->isEmpty()){
            $this->info("Không có đơn hàng nào quá hạn!");
            return;
        }

        foreach($expiredOrders as $order){
            DB::transaction(function () use ($order, $inventoryService){
                $inventoryService->restoreStock($order);

                foreach($order->details as $detail){
                    if($detail->flash_sale_id){
                        FlashSaleItem::where('flash_sale_id', $detail->flash_sale_id)
                            ->where('product_variant_id', $detail->product_variant_id)
                            ->decrement('sold_quantity', $detail->quantity);
                    }
                }

                if ($order->voucher_id) {
                    Voucher::where('id', $order->voucher_id)->decrement('used_count');
                    VoucherUsage::where('order_id', $order->id)->delete();
                }

                Order::where('id', $order->id)->update(['order_status' => 0]);

                $this->info("Đã hủy và hoàn trả dữ liệu đơn hàng #{$order->id}");
            });
        }
    }
}
